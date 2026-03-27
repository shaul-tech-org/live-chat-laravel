# 기술 아키텍처 — LCHAT (Laravel)

---

## 1. 시스템 아키텍처

```
┌─────────────┐     ┌──────────────┐     ┌──────────────┐
│ 방문자 브라우저 │     │ 관리자 브라우저  │     │  텔레그램 앱  │
│  Widget SDK  │     │  Admin Blade  │     │   Bot API    │
│  + Echo.js   │     │  + Echo.js    │     │              │
└──────┬───────┘     └──────┬────────┘     └──────┬───────┘
       │ HTTPS + WSS         │ HTTPS + WSS         │ HTTPS
       ▼                    ▼                     ▼
┌─────────────────────────────────────────────────────────┐
│                  Nginx (Reverse Proxy)                   │
│  lchat.shaul.kr:443                                     │
│  ├── /app/* → Reverb (:8080) [WebSocket]                │
│  ├── /webhook/* → PHP-FPM (:9000)                       │
│  └── /* → PHP-FPM (:9000) [HTTP]                        │
└─────────────────────────┬───────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼                               ▼
┌──────────────────┐           ┌──────────────────┐
│  PHP-FPM (:9000) │           │  Reverb (:8080)  │
│  Laravel 13      │           │  WebSocket 서버   │
│                  │           │                  │
│  Controllers     │◄─ Redis ─►│  Broadcasting    │
│  Middleware       │  Pub/Sub  │  Channels        │
│  Eloquent ORM    │           │  Pusher Protocol │
│  Services        │           │                  │
│  Events          │           │                  │
└───┬──────┬───┬───┘           └──────────────────┘
    │      │   │
    ▼      ▼   ▼
┌────────┐ ┌────────┐ ┌────────┐
│  PG 18 │ │Mongo 8 │ │Redis 8 │
│ :5432  │ │ :27017 │ │ :6379  │
│        │ │        │ │        │
│tenants │ │messages│ │pub/sub │
│agents  │ │events  │ │session │
│rooms   │ │audit   │ │cache   │
│feedback│ │        │ │queue   │
│faq     │ │        │ │rate    │
└────────┘ └────────┘ └────────┘
```

### Go 아키텍처와 비교

| 항목 | Go (현재) | Laravel (목표) |
|------|----------|--------------|
| HTTP 서버 | Echo (단일 바이너리) | PHP-FPM + Nginx (2 프로세스) |
| WS 서버 | 자체 Hub (같은 프로세스) | Reverb (별도 프로세스) |
| 메시지 라우팅 | Hub goroutine (인메모리) | Redis Pub/Sub → Reverb |
| 이벤트 처리 | 동기 (goroutine) | Queue Worker (비동기) |
| 프로세스 수 | 1 (Go 바이너리) | 3~4 (FPM + Nginx + Reverb + Worker) |

---

## 2. WebSocket 아키텍처 — Laravel Reverb

### Go Hub → Laravel Reverb 전환

```
[Go 현재]
방문자 ──WSS──► Hub(goroutine) ──► Room ──► 클라이언트
                    │
                    └──► MongoDB 저장
                    └──► 텔레그램 알림


[Laravel 목표]
방문자 ──WSS──► Reverb ──► Echo.js
                  ▲
                  │ Redis Pub/Sub
                  ▼
PHP-FPM ──► broadcast(new MessageSent(...))
                  │
                  ├──► MongoDB 저장
                  └──► 텔레그램 알림 (Queue)
```

### 시퀀스 다이어그램 — 메시지 전송

```
방문자         Echo.js        Reverb        PHP-FPM        MongoDB
  │              │              │              │              │
  │  메시지 입력   │              │              │              │
  ├─────────────►│              │              │              │
  │              │  whisper or  │              │              │
  │              │  POST /api   │              │              │
  │              ├─────────────────────────────►│              │
  │              │              │              │  저장          │
  │              │              │              ├─────────────►│
  │              │              │              │              │
  │              │              │  broadcast   │              │
  │              │              │◄─────────────┤              │
  │              │              │              │              │
  │              │  이벤트 수신  │              │              │
  │              │◄─────────────┤              │              │
  │  메시지 표시  │              │              │              │
  │◄─────────────┤              │              │              │
```

### 의사결정: 메시지 전송 방식

**Option A: Whisper (클라이언트 → Reverb → 클라이언트)**
- Echo.js `whisper` 사용
- 서버 경유 없이 Reverb가 직접 라우팅
- 단점: 서버 검증/저장 불가

**Option B: HTTP + Broadcast (클라이언트 → API → Broadcast) ✅ 선택**
- 클라이언트가 POST /api/rooms/{id}/messages로 전송
- 서버에서 검증 → MongoDB 저장 → broadcast(new MessageSent)
- 장점: 서버 권위 (검증, 저장, FAQ 매칭, 텔레그램 알림)

**WHY Option B:**
- Go 버전의 서버-권위 아키텍처 유지
- XSS 필터링, Rate Limit, FAQ 매칭 등 서버 로직 필요
- 메시지 영속성 보장 (MongoDB 저장 후 브로드캐스트)

### 채널 구조

```php
// routes/channels.php

// 채팅방 채널 — 방문자 + 상담원
Broadcast::channel('chat.{roomId}', ChatChannel::class);

// 관리자 채널 — 모든 방의 메시지 수신
Broadcast::channel('admin.{tenantId}', AdminChannel::class);
```

**ChatChannel 인증:**
```php
class ChatChannel
{
    public function join($user, string $roomId): bool
    {
        // API 키로 테넌트 확인 → 방이 해당 테넌트 소속인지 확인
        // 또는 admin 토큰 확인
        return $user->canAccessRoom($roomId);
    }
}
```

---

## 3. DB 스키마

### PostgreSQL (Eloquent Migration)

> **설계 원칙:**
> - FK 대신 **약한 연결** (uuid 컬럼 + 인덱스, CASCADE 없음) → 유연한 데이터 관리
> - **Soft Delete** (deleted_at) 전 테이블 적용 → 데이터 복원 가능
> - 모든 테이블/컬럼에 **comment** 작성

```php
// =============================================
// tenants — 테넌트 (멀티테넌트 SaaS의 사이트 단위)
// =============================================
Schema::create('tenants', function (Blueprint $table) {
    $table->uuid('id')->primary()->comment('테넌트 고유 ID');
    $table->string('name', 100)->comment('테넌트 이름 (사이트명)');
    $table->string('domain', 255)->nullable()->comment('허용 도메인 (CORS 검증용)');
    $table->string('api_key', 64)->unique()->comment('위젯 인증용 API 키 (ck_live_ 접두사)');
    $table->jsonb('widget_config')->default('{}')->comment('위젯 설정 (색상, 위치, 환영메시지, 프리챗, 프로액티브, 운영시간 등)');
    $table->bigInteger('telegram_chat_id')->nullable()->comment('텔레그램 알림 수신 채팅 ID');
    $table->text('auto_reply_message')->nullable()->comment('오프라인 자동 응답 메시지');
    $table->string('owner_id', 255)->comment('테넌트 소유자 ID (인증 시스템 참조)');
    $table->boolean('is_active')->default(true)->comment('활성 상태 (false: 비활성화)');
    $table->timestamps();
    $table->softDeletes()->comment('소프트 삭제 시각');

    $table->index('domain');
    $table->index('is_active');
});
DB::statement("COMMENT ON TABLE tenants IS '테넌트 — 멀티테넌트 SaaS의 사이트 단위. 각 테넌트는 독립된 API 키, 위젯 설정, 상담원을 가짐'");

// =============================================
// agents — 상담원 (테넌트별 관리자/상담원)
// =============================================
Schema::create('agents', function (Blueprint $table) {
    $table->uuid('id')->primary()->comment('상담원 고유 ID');
    $table->uuid('tenant_id')->comment('소속 테넌트 ID (tenants.id 참조, 약한 연결)');
    $table->string('user_id', 255)->comment('인증 시스템의 사용자 ID (Keycloak 또는 Built-in)');
    $table->string('name', 100)->nullable()->comment('상담원 표시 이름');
    $table->string('email', 255)->nullable()->comment('상담원 이메일');
    $table->string('role', 20)->default('agent')->comment('역할 (admin: 관리자, agent: 상담원)');
    $table->boolean('is_online')->default(false)->comment('현재 온라인 여부');
    $table->boolean('is_active')->default(true)->comment('활성 상태');
    $table->timestamp('last_seen_at')->nullable()->comment('마지막 접속 시각');
    $table->timestamps();
    $table->softDeletes()->comment('소프트 삭제 시각');

    $table->index('tenant_id');
    $table->unique(['tenant_id', 'user_id']);
});
DB::statement("COMMENT ON TABLE agents IS '상담원 — 테넌트별 채팅 응대 담당자. admin/agent 역할 구분'");

// =============================================
// chat_rooms — 채팅방 (방문자 1명 = 1방)
// =============================================
Schema::create('chat_rooms', function (Blueprint $table) {
    $table->uuid('id')->primary()->comment('채팅방 고유 ID');
    $table->uuid('tenant_id')->comment('소속 테넌트 ID (tenants.id 참조, 약한 연결)');
    $table->string('visitor_id', 64)->comment('방문자 식별자 (localStorage 기반)');
    $table->string('visitor_name', 100)->nullable()->comment('방문자 이름 (프리챗 폼 또는 자동 생성)');
    $table->string('visitor_email', 255)->nullable()->comment('방문자 이메일 (선택 입력)');
    $table->string('status', 20)->default('open')->comment('상태 (open: 진행 중, closed: 종료)');
    $table->uuid('assigned_agent_id')->nullable()->comment('담당 상담원 ID (agents.id 참조, 약한 연결)');
    $table->timestamps();
    $table->timestamp('closed_at')->nullable()->comment('채팅 종료 시각');
    $table->softDeletes()->comment('소프트 삭제 시각');

    $table->index(['tenant_id', 'status']);
    $table->index('visitor_id');
    $table->index('assigned_agent_id');
});
DB::statement("COMMENT ON TABLE chat_rooms IS '채팅방 — 방문자와 상담원 간 대화 세션. 방문자당 복수 방 가능'");

// =============================================
// feedbacks — 피드백 (채팅 종료 후 만족도 조사)
// =============================================
Schema::create('feedbacks', function (Blueprint $table) {
    $table->uuid('id')->primary()->comment('피드백 고유 ID');
    $table->uuid('tenant_id')->comment('소속 테넌트 ID (tenants.id 참조, 약한 연결)');
    $table->uuid('room_id')->comment('채팅방 ID (chat_rooms.id 참조, 약한 연결)');
    $table->string('visitor_email', 255)->nullable()->comment('방문자 이메일');
    $table->tinyInteger('rating')->comment('만족도 점수 (1~5, 이모지 매핑)');
    $table->text('comment')->nullable()->comment('추가 의견 (자유 텍스트)');
    $table->text('page_url')->nullable()->comment('피드백 제출 시 페이지 URL');
    $table->timestamps();
    $table->softDeletes()->comment('소프트 삭제 시각');

    $table->index(['tenant_id', 'created_at']);
    $table->index('room_id');
});
DB::statement("COMMENT ON TABLE feedbacks IS '피드백 — 채팅 종료 후 방문자 만족도 조사 (이모지 1~5점 + 코멘트)'");

// =============================================
// faq_entries — FAQ 자동 응답 (키워드 매칭)
// =============================================
Schema::create('faq_entries', function (Blueprint $table) {
    $table->uuid('id')->primary()->comment('FAQ 고유 ID');
    $table->uuid('tenant_id')->comment('소속 테넌트 ID (tenants.id 참조, 약한 연결)');
    $table->string('keyword', 255)->comment('매칭 키워드 (대소문자 무시, 부분 일치)');
    $table->text('answer')->comment('자동 응답 내용');
    $table->boolean('is_active')->default(true)->comment('활성 상태 (false: 비활성)');
    $table->timestamps();
    $table->softDeletes()->comment('소프트 삭제 시각');

    $table->index('tenant_id');
    $table->index(['tenant_id', 'keyword']);
});
DB::statement("COMMENT ON TABLE faq_entries IS 'FAQ 자동 응답 — 방문자 메시지에 키워드 매칭 시 자동으로 답변 전송'");
```

### MongoDB (jenssegers/mongodb)

```php
// Message Model
class Message extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'messages';

    protected $fillable = [
        'room_id', 'tenant_id', 'sender_type', 'sender_name',
        'content', 'content_type', 'file_url', 'is_read',
        'reply_to', 'created_at'
    ];
}
// 인덱스: { room_id: 1, created_at: 1 }
// TTL: created_at, 90일

// WidgetEvent Model
class WidgetEvent extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'widget_events';
    // TTL: 180일
}

// AuditLog Model
class AuditLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'audit_logs';
    // TTL: 365일
}

// Reaction Model
class Reaction extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reactions';
}
```

### DB 분리 설계 (Go 동일)

| DB | 데이터 | WHY |
|----|--------|-----|
| **PostgreSQL** | tenants, agents, chat_rooms, feedbacks, faq_entries | ACID, Eloquent, Soft Delete |
| **MongoDB** | messages, widget_events, audit_logs, reactions | append-heavy, TTL, 유연한 스키마 |
| **Redis** | Pub/Sub, session, cache, queue, rate_limit | 실시간, Broadcasting |

### 설계 원칙

| 원칙 | 설명 |
|------|------|
| **약한 연결** | FK 대신 uuid 컬럼 + 인덱스. CASCADE 없음. 참조 무결성은 애플리케이션 레벨에서 관리 |
| **Soft Delete** | 모든 테이블에 deleted_at 적용. 실수로 삭제해도 복원 가능. 쿼리 시 자동 필터링 (Eloquent SoftDeletes) |
| **Comment** | 모든 테이블/컬럼에 한국어 comment. DB 탐색 시 의미 즉시 파악 |
| **UUID PK** | 모든 테이블 UUID primary key. 분산 환경 대비, URL 추측 방지 |
| **Enum 없음** | DB enum 사용 금지. 상태값은 string 컬럼 + 애플리케이션 상수로 관리 |

### 애플리케이션 상수 (DB enum 대체)

```php
// app/Enums/RoomStatus.php
enum RoomStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}

// app/Enums/AgentRole.php
enum AgentRole: string
{
    case Admin = 'admin';
    case Agent = 'agent';
}

// app/Enums/SenderType.php
enum SenderType: string
{
    case Visitor = 'visitor';
    case Agent = 'agent';
    case System = 'system';
}

// app/Enums/ContentType.php
enum ContentType: string
{
    case Text = 'text';
    case Image = 'image';
    case File = 'file';
}

// app/Enums/EventType.php
enum EventType: string
{
    case PageView = 'page_view';
    case WidgetOpen = 'widget_open';
    case WidgetClose = 'widget_close';
    case ChatStart = 'chat_start';
    case ChatEnd = 'chat_end';
}
```

**WHY DB enum 대신 앱 상수:**
- DB enum 변경 시 ALTER TABLE + 마이그레이션 필요 → 무중단 배포 어려움
- PHP 8.1+ backed enum으로 타입 안전성 확보
- 새로운 상태값 추가 시 코드 배포만으로 완료 (DB 변경 불필요)
- 다른 DB(MySQL ↔ PG)로 전환 시 호환성 문제 없음

---

## 4. 위젯 아키텍처

### v1.0: JS SDK + Echo.js

```
widget.js (호스트 DOM)
  │
  ├── 버블/패널 DOM 생성
  ├── Echo.js 초기화
  │     └── Reverb WSS 연결
  ├── Echo.private('chat.' + roomId)
  │     ├── .listen('MessageSent', ...)
  │     ├── .listen('ReactionAdded', ...)
  │     ├── .listenForWhisper('typing', ...)
  │     └── .listen('AgentOffline', ...)
  └── HTTP API 호출 (메시지 전송, 파일 업로드 등)
```

### v2.0: 하이브리드 (JS SDK + iframe)

```
widget.js (호스트 DOM)              iframe (격리)
  │                                   │
  ├── 버블 DOM                        │
  ├── 패널 외곽 DOM                   │
  │                                   │
  │   postMessage ──────────────────►│
  │   {type:"init", apiKey, roomId}  │
  │                                   │
  │                          ┌────────┤
  │                          │ Echo.js│
  │                          │ 채팅 UI│
  │                          │ 메시지 │
  │                          │ 입력   │
  │                          └────────┤
  │                                   │
  │   ◄──────────────────── postMessage
  │   {type:"unread", count: 3}      │
```

---

## 5. 보안 아키텍처

```
┌─── 외부 요청 ──────────────────────────────────────┐
│                                                     │
│  1. Nginx (TLS + 헤더 제거)                         │
│     ↓                                              │
│  2. Laravel Middleware Pipeline                     │
│     ├── TrustProxies                               │
│     ├── CORS (config/cors.php)                     │
│     ├── XssSanitizer (custom)                      │
│     ├── ThrottleRequests (내장 Rate Limit)          │
│     ├── ApiKeyAuth (custom) — 위젯 라우트           │
│     └── AdminAuth (custom) — 관리자 라우트          │
│     ↓                                              │
│  3. Controller (비즈니스 로직)                       │
│     ├── FormRequest 검증                            │
│     ├── Eloquent parameterized query               │
│     └── Laravel Filesystem (파일 MIME 검증)         │
│     ↓                                              │
│  4. Reverb (WebSocket)                             │
│     ├── /broadcasting/auth (채널 인증)              │
│     ├── Origin 검증 (config/reverb.php)            │
│     └── Rate Limit (채널 레벨)                      │
└─────────────────────────────────────────────────────┘
```

---

## 6. 배포 아키텍처 (Docker Compose)

```
┌─── Docker Compose ─────────────────────────────────┐
│                                                     │
│  ┌─── nginx ───┐  ┌─── app (PHP-FPM) ───┐         │
│  │ :80 → :443  │  │ Laravel 13           │         │
│  │ TLS 종료    │─►│ Controllers          │         │
│  │ WSS 프록시  │  │ Eloquent             │         │
│  └─────────────┘  │ Services             │         │
│                    └──────────────────────┘         │
│                                                     │
│  ┌─── reverb ──┐  ┌─── worker ──────────┐         │
│  │ :8080 (WS)  │  │ php artisan         │         │
│  │ Broadcasting│◄►│ queue:work          │         │
│  │ Channels    │  │ (이벤트 처리)        │         │
│  └─────────────┘  └──────────────────────┘         │
│                                                     │
│  ┌─── postgres ┐  ┌─── mongodb ┐  ┌─── redis ──┐  │
│  │ PG 18       │  │ Mongo 8    │  │ Redis 8    │  │
│  │ :5432       │  │ :27017     │  │ :6379      │  │
│  └─────────────┘  └────────────┘  └────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Nginx 설정

```nginx
server {
    listen 443 ssl;
    server_name lchat.shaul.kr;

    # Laravel HTTP
    location / {
        proxy_pass http://app:9000;
        # PHP-FPM fastcgi 또는 proxy
    }

    # Reverb WebSocket
    location /app {
        proxy_pass http://reverb:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    # Broadcasting auth
    location /broadcasting {
        proxy_pass http://app:9000;
    }
}
```

---

## 7. 프로젝트 구조 (최종)

```
live-chat-laravel/
├── app/
│   ├── Events/           # MessageSent, TypingStarted, ReactionAdded...
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/      # RoomController, MessageController, UploadController...
│   │   │   ├── Admin/    # TenantController, AgentController, StatsController...
│   │   │   └── Auth/     # LoginController, OAuthController
│   │   └── Middleware/    # ApiKeyAuth, AdminAuth, XssSanitizer
│   ├── Models/            # Tenant, Agent, ChatRoom, Feedback, FaqEntry
│   ├── Models/Mongo/      # Message, WidgetEvent, AuditLog, Reaction
│   ├── Services/          # BuiltinAuthService, TelegramService, FaqMatcherService
│   └── Broadcasting/      # ChatChannel, AdminChannel
├── database/migrations/   # PG 5테이블
├── resources/views/       # Blade 템플릿 (admin, auth, chat, pages)
├── public/js/widget.js    # 위젯 SDK (Echo.js 연동)
├── routes/                # api.php, web.php, channels.php
├── config/                # chat.php, reverb.php, broadcasting.php
├── tests/                 # PHPUnit + Playwright
├── docker/                # Dockerfile, nginx.conf
├── docker-compose.yml
├── .env.example
├── LICENSE                # MIT
├── README.md
├── CONTRIBUTING.md
└── CHANGELOG.md
```
