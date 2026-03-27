# API 명세서 — LCHAT (Laravel)

> Base URL: `https://lchat.shaul.kr`
> Go 버전 36개 엔드포인트 1:1 매핑 + Laravel 추가 엔드포인트
> 인증: API 키 (위젯) / Sanctum 토큰 또는 Built-in Auth (관리자)

---

## 1. 인증 방식

### 위젯 (방문자)
- `X-API-Key` 헤더 또는 `?api_key=` 쿼리 파라미터
- WebSocket: Echo.js가 API 키를 auth 헤더로 전달

### 관리자 (Built-in Auth)
- POST /api/auth/login → 토큰 발급
- `Authorization: Bearer {token}` 또는 쿠키 `shaul_access_token`
- Built-in Auth: ADMIN_EMAIL + ADMIN_PASSWORD (.env)
- Keycloak: 선택적 (AUTH_API_URL 설정 시)

---

## 2. Go → Laravel 라우트 매핑

### Public

| Go | Laravel | 메서드 | 설명 |
|----|---------|--------|------|
| /api/health | /api/health | GET | 헬스체크 |
| /demo | /demo | GET | 위젯 데모 |
| /login | /login | GET | 로그인 페이지 |
| /callback | /auth/callback | GET | OAuth 콜백 |
| /admin | /admin | GET | 관리자 대시보드 |
| /api/docs | /docs | GET | API 문서 |
| /m/:room_id | /m/{room_id} | GET | 모바일 채팅 |
| /metrics | /metrics | GET | Prometheus (선택) |
| /api/widget/config | /api/widget/config | GET | 위젯 설정 |

### Auth (POST /api/auth/login)

| Go | Laravel | 메서드 | 설명 |
|----|---------|--------|------|
| /api/auth/login | /api/auth/login | POST | Built-in 로그인 |
| - | /api/auth/logout | POST | 로그아웃 (신규) |

### Widget (API 키 인증)

| Go | Laravel | 메서드 | 설명 |
|----|---------|--------|------|
| /api/rooms (POST) | /api/rooms | POST | 채팅방 생성 |
| /api/rooms (GET) | /api/rooms | GET | 방문자 대화 목록 |
| /api/rooms/:id/messages | /api/rooms/{id}/messages | GET | 메시지 이력 |
| /api/rooms/:id/transcript | /api/rooms/{id}/transcript | POST | 이메일 트랜스크립트 |
| /api/feedbacks | /api/feedbacks | POST | 피드백 제출 |
| /api/events | /api/events | POST | 이벤트 트래킹 |
| /api/upload | /api/upload | POST | 파일 업로드 |
| /api/link-preview | /api/link-preview | GET | 링크 미리보기 |

### Admin (Sanctum/Built-in 인증 + admin role)

| Go | Laravel | 메서드 | 설명 |
|----|---------|--------|------|
| /api/admin/rooms | /api/admin/rooms | GET | 채팅방 목록 |
| /api/admin/rooms/:id | /api/admin/rooms/{id} | PATCH | 채팅방 상태 변경 |
| /api/admin/rooms/:id/messages | /api/admin/rooms/{id}/messages | GET | 메시지 조회 (admin) |
| /api/admin/rooms/:id/read | /api/admin/rooms/{id}/read | POST | 읽음 처리 |
| /api/admin/tenants | /api/admin/tenants | GET | 테넌트 목록 |
| /api/admin/tenants | /api/admin/tenants | POST | 테넌트 생성 |
| /api/admin/tenants/:id | /api/admin/tenants/{id} | PATCH | 테넌트 수정 |
| /api/admin/tenants/:id/rotate-key | /api/admin/tenants/{id}/rotate-key | POST | API 키 재발급 |
| /api/admin/feedbacks | /api/admin/feedbacks | GET | 피드백 목록 |
| /api/admin/agents | /api/admin/agents | GET | 상담원 목록 |
| /api/admin/agents | /api/admin/agents | POST | 상담원 추가 |
| /api/admin/agents/:id | /api/admin/agents/{id} | DELETE | 상담원 삭제 |
| /api/admin/faq | /api/admin/faq | GET | FAQ 목록 |
| /api/admin/faq | /api/admin/faq | POST | FAQ 생성 |
| /api/admin/faq/:id | /api/admin/faq/{id} | DELETE | FAQ 삭제 |
| /api/admin/stats | /api/admin/stats | GET | 통계 |
| /api/admin/link-preview | /api/admin/link-preview | GET | 링크 미리보기 (admin) |

### WebSocket (Laravel Reverb)

| Go | Laravel | 프로토콜 | 설명 |
|----|---------|---------|------|
| /ws | wss://lchat.shaul.kr/app/{key} | Reverb(Pusher) | WebSocket |
| - | /broadcasting/auth | POST | 채널 인증 (자동) |

### Webhook

| Go | Laravel | 메서드 | 설명 |
|----|---------|--------|------|
| /webhook/telegram | /webhook/telegram | POST | 텔레그램 봇 |

**총: 38개 엔드포인트** (Go 36 + logout + broadcasting/auth)

---

## 3. Laravel 라우트 파일

### routes/api.php

```php
<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;

// Public
Route::get('/health', [Api\HealthController::class, 'index']);
Route::get('/widget/config', [Api\WidgetConfigController::class, 'show']);

// Auth
Route::post('/auth/login', [Auth\LoginController::class, 'login']);
Route::post('/auth/logout', [Auth\LoginController::class, 'logout']);

// Widget (API Key middleware)
Route::middleware('api.key')->group(function () {
    Route::post('/rooms', [Api\RoomController::class, 'store']);
    Route::get('/rooms', [Api\RoomController::class, 'visitorRooms']);
    Route::get('/rooms/{id}/messages', [Api\MessageController::class, 'index']);
    Route::post('/rooms/{id}/transcript', [Api\TranscriptController::class, 'store']);
    Route::post('/feedbacks', [Api\FeedbackController::class, 'store']);
    Route::post('/events', [Api\EventController::class, 'store']);
    Route::post('/upload', [Api\UploadController::class, 'store']);
    Route::get('/link-preview', [Api\LinkPreviewController::class, 'show']);
});

// Admin (Sanctum/Built-in + admin role)
Route::middleware('admin.auth')->prefix('admin')->group(function () {
    Route::get('/rooms', [Admin\RoomController::class, 'index']);
    Route::patch('/rooms/{id}', [Admin\RoomController::class, 'update']);
    Route::get('/rooms/{id}/messages', [Admin\MessageController::class, 'index']);
    Route::post('/rooms/{id}/read', [Admin\RoomController::class, 'markRead']);

    Route::apiResource('tenants', Admin\TenantController::class)->except(['show', 'destroy']);
    Route::post('/tenants/{id}/rotate-key', [Admin\TenantController::class, 'rotateKey']);

    Route::get('/feedbacks', [Admin\FeedbackController::class, 'index']);

    Route::get('/agents', [Admin\AgentController::class, 'index']);
    Route::post('/agents', [Admin\AgentController::class, 'store']);
    Route::delete('/agents/{id}', [Admin\AgentController::class, 'destroy']);

    Route::get('/faq', [Admin\FaqController::class, 'index']);
    Route::post('/faq', [Admin\FaqController::class, 'store']);
    Route::delete('/faq/{id}', [Admin\FaqController::class, 'destroy']);

    Route::get('/stats', [Admin\StatsController::class, 'index']);
    Route::get('/link-preview', [Api\LinkPreviewController::class, 'show']);
});

// Webhook
Route::post('/webhook/telegram', [Api\TelegramController::class, 'webhook']);
```

### routes/web.php

```php
Route::get('/login', [Auth\LoginController::class, 'showLogin'])->name('login');
Route::get('/auth/callback', [Auth\OAuthController::class, 'callback']);
Route::get('/admin', [Admin\DashboardController::class, 'index'])->middleware('admin.auth');
Route::get('/demo', fn() => view('pages.demo'));
Route::get('/docs', fn() => view('pages.docs'));
Route::get('/m/{room_id}', fn($id) => view('chat.mobile', ['roomId' => $id]));
```

### routes/channels.php

```php
// Private channel: chat room (visitor API key or admin token)
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return $user->canAccessRoom($roomId);
});

// Private channel: admin dashboard
Broadcast::channel('admin.{tenantId}', function ($user, $tenantId) {
    return $user->isAdmin();
});
```

---

## 4. Broadcasting 이벤트

### Go WS 메시지 → Laravel Event 매핑

| Go WSMessage.Type | Laravel Event | 채널 | 설명 |
|-------------------|--------------|------|------|
| message | MessageSent | chat.{roomId} + admin.{tenantId} | 채팅 메시지 |
| typing | TypingStarted | chat.{roomId} | 타이핑 중 (whisper) |
| read / read_ack | MessageRead | chat.{roomId} | 읽음 확인 |
| reaction | ReactionAdded | chat.{roomId} | 이모지 리액션 |
| history | - | (연결 시 HTTP로 전송) | 이전 대화 |
| offline | AgentOffline | chat.{roomId} | 오프라인 자동 응답 |
| system | SystemMessage | chat.{roomId} | 시스템 메시지 |
| error | - | (HTTP 응답) | 에러 |

### 이벤트 페이로드 예시

```php
// MessageSent Event
class MessageSent implements ShouldBroadcast
{
    public string $id;
    public string $room_id;
    public string $sender_type; // visitor | agent | system
    public string $sender_name;
    public string $content;
    public string $content_type; // text | image | file
    public ?string $file_url;
    public ?ReplyTo $reply_to;
    public string $created_at;

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->room_id),
            new PrivateChannel('admin.' . $this->tenant_id),
        ];
    }
}
```

---

## 5. 에러 코드 (Go 동일)

| HTTP | 코드 | 설명 |
|------|------|------|
| 400 | BAD_REQUEST | 요청 형식 오류 |
| 401 | UNAUTHORIZED | 인증 실패 |
| 403 | FORBIDDEN | 권한 없음 |
| 404 | NOT_FOUND | 리소스 없음 |
| 413 | PAYLOAD_TOO_LARGE | 파일 크기 초과 |
| 429 | RATE_LIMITED | 요청 빈도 초과 |
| 500 | INTERNAL_ERROR | 서버 오류 |

---

## 6. 주요 차이점 (Go vs Laravel)

| 항목 | Go (현재) | Laravel (목표) |
|------|----------|--------------|
| WS 연결 | /ws?api_key=&room_id=&role= | Reverb: /app/{key} + Echo.js |
| WS 인증 | 쿼리 파라미터 | /broadcasting/auth (POST) |
| WS 프로토콜 | 자체 JSON | Pusher 프로토콜 (Reverb 호환) |
| 히스토리 전송 | WS 연결 시 자동 | HTTP GET /rooms/{id}/messages |
| Rate Limit | 자체 Redis | Laravel RateLimiter |
| 파일 업로드 | multipart → MinIO/Local | multipart → Laravel Filesystem |
| 이메일 | TODO (JSON 반환) | Laravel Mail (SMTP 실제 발송) |
