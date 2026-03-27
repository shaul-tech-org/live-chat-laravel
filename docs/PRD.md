# PRD — Live Chat Widget (Laravel 마이그레이션)

## 1. 제품 개요

### 배경
Go(Echo v4)로 구현된 Live Chat Widget을 Laravel 12로 마이그레이션한다. 기존 기능 100%를 유지하면서 Laravel 생태계의 장점(Eloquent, Broadcasting, Mail, Filesystem 등)을 활용한다.

### 목적
- 기존 Go 버전의 모든 기능을 Laravel로 1:1 이식
- Laravel Reverb로 WebSocket 아키텍처 현대화
- Blade 템플릿으로 Admin UI 개선
- Self-Hosted 환경에서 PHP 기반으로 배포 용이성 확보

### 도메인
- URL: `https://lchat.shaul.kr` (신규 도메인)
- Widget SDK: `https://lchat.shaul.kr/js/widget.js`
- 포트: 8100 (HTTP) + 8080 (Reverb WS)
- GitHub: `shaul-tech-org/live-chat-laravel` (public, MIT License)

---

## 2. 마이그레이션 범위

### Go → Laravel 매핑

| Go 컴포넌트 (현재) | Laravel 컴포넌트 (목표) | 이슈 |
|-------------------|----------------------|------|
| Echo v4 Router | Laravel Router + Controller | LCHAT-12~24 |
| gorilla/websocket Hub | Laravel Reverb + Broadcasting | LCHAT-25~30 |
| Go html/template | Blade 템플릿 | LCHAT-31~37 |
| pgx Raw SQL | Eloquent ORM + Migration | LCHAT-8 |
| mongo-driver v2 | jenssegers/mongodb | LCHAT-8 |
| go-redis v9 | Laravel Redis (내장) | LCHAT-7 |
| 자체 Auth (builtin) | BuiltinAuthService | LCHAT-10 |
| 자체 Auth (keycloak) | KeycloakAuthService | LCHAT-11 |
| 자체 Middleware | Laravel Middleware | LCHAT-9 |
| 자체 Storage | Laravel Filesystem | LCHAT-19 |
| 자체 Rate Limit | Laravel RateLimiter (내장) | LCHAT-9 |
| widget.js (2,334줄) | 그대로 유지 + Echo.js 연동 | LCHAT-30 |
| admin.html (Alpine.js) | Blade + Alpine.js | LCHAT-32~33 |

### 기능 보존 체크리스트 (Go 95건 이슈 → Laravel)

#### 핵심 기능 (반드시 이식)
- [x] 채팅방 생성/조회/닫기
- [x] WebSocket 실시간 메시지 (양방향)
- [x] 위젯 SDK (script 1줄 삽입)
- [x] 관리자 대시보드 (6탭: 채팅/테넌트/상담원/피드백/통계/FAQ)
- [x] 텔레그램 봇 연동 (양방향)
- [x] 멀티테넌트 (API 키)
- [x] Built-in Admin 인증 (Keycloak 불필요)
- [x] 파일/이미지 첨부 + 미리보기
- [x] 피드백 (이모지 방식)
- [x] FAQ 자동 응답

#### UX 기능 (벤치마킹 결과, 전부 이식)
- [x] 메시지 그룹핑 + 날짜 구분선
- [x] 타이핑 인디케이터 + 읽음 확인 ✓✓
- [x] 프리챗 폼 (이름/이메일)
- [x] 프로액티브 메시지 (자동 팝업)
- [x] 운영 시간 + 예상 응답 시간
- [x] 다크 모드 (시스템 연동)
- [x] 다국어 (ko/en)
- [x] 링크 미리보기 (OG Tag)
- [x] 답장/인용 메시지
- [x] 대화 목록 (복수 대화)
- [x] 이모지 리액션 (👍❤️😄🙏)
- [x] 메시지 검색 (Admin)
- [x] 풀-투-로드 (무한 스크롤)
- [x] 새 메시지 알림 + 미읽 배지
- [x] 드래그&드롭 + 클립보드 붙여넣기
- [x] 알림음 ON/OFF
- [x] iOS 모바일 전용 페이지

#### 보안 (전부 이식)
- [x] XSS 필터링
- [x] CORS 명시적 도메인
- [x] Rate Limit (HTTP + WS)
- [x] WS CheckOrigin
- [x] 파일 업로드 MIME detection
- [x] 텔레그램 Webhook secret
- [x] Admin role 권한 체크
- [x] API 키 마스킹

#### 운영
- [x] Prometheus /metrics (선택적)
- [x] 이벤트 트래킹 (MongoDB)
- [x] Docker Compose (PG18 + MongoDB8 + Redis8)
- [x] Self-Hosted 설치 가이드

---

## 3. MVP 범위

### MVP = Go 기능 100% 이식

Go 버전이 이미 완성된 제품이므로, Laravel MVP는 **Go 기능 전체 이식**이다.
단계적으로 Phase 1~6으로 나누되, 모든 Phase를 완료해야 Go 서버를 교체할 수 있다.

### Go 서버 교체 게이트

- [ ] Go API 36개 엔드포인트 → Laravel 1:1 동작
- [ ] WebSocket (Reverb) 실시간 채팅 동작
- [ ] Admin 대시보드 Blade 전환 완료
- [ ] Playwright 테스트 Go 76건 기준 통과
- [ ] Docker Compose self-hosted 동작
- [ ] 기존 PG/MongoDB 데이터 호환

---

## 4. 기술 스택

| 영역 | 기술 | WHY |
|------|------|-----|
| 프레임워크 | Laravel 12 | PHP 생태계 최대, Reverb/Broadcasting 내장 |
| PHP | 8.4+ | 최신 성능 + 타입 시스템 |
| WebSocket | Laravel Reverb | 공식 WS 서버, Broadcasting 통합, self-hosted |
| ORM | Eloquent | 생산성, 마이그레이션, 관계 정의 |
| MongoDB | jenssegers/mongodb | Eloquent 호환 MongoDB 드라이버 |
| 인증 | Sanctum + 자체 Built-in | 토큰 + 쿠키, Keycloak 선택적 |
| 파일 | Laravel Filesystem | 로컬/S3 추상화, 설정만 변경 |
| 메일 | Laravel Mail | SMTP 내장, 트랜스크립트 이메일 |
| 캐시 | Laravel Redis | 내장, Rate Limit + Pub/Sub |
| 템플릿 | Blade + Alpine.js | 서버 사이드 렌더링 + 반응형 |
| 테스트 | PHPUnit + Playwright | 단위 + E2E |
| 배포 | Docker (PHP-FPM + Nginx) | 표준 PHP 배포 |

---

## 5. 위젯 아키텍처 전략

### v1.0: JS SDK (빠른 출시)
- `<script>` 1줄로 호스트 DOM에 직접 위젯 생성
- Go 버전 widget.js 기반 (Echo.js 연동만 변경)
- 장점: 빠른 출시, 호스트 페이지 컨텍스트 활용
- 단점: CSS 충돌 (!important), iOS 키보드 밀림

### v2.0: 하이브리드 (JS SDK + iframe)
- 버블 + 패널 외곽: JS SDK (호스트 DOM, 테마 통합)
- 채팅 본문: iframe 격리 (`lchat.shaul.kr/widget/frame?room_id=X`)
- JS SDK ↔ iframe: postMessage 통신
- 장점: CSS/JS 완전 격리, iOS 키보드 해결, 보안 강화
- 단점: 구현 복잡도 증가, postMessage 프로토콜 필요

### WHY 하이브리드?
| 문제 | JS SDK만 | iframe만 | 하이브리드 |
|------|---------|---------|-----------|
| CSS 충돌 | ❌ !important 남발 | ✅ 격리 | ✅ 격리 |
| iOS 키보드 | ❌ position:fixed 밀림 | ✅ 독립 뷰포트 | ✅ 독립 뷰포트 |
| 호스트 정보 | ✅ 직접 접근 | ❌ postMessage | ✅ JS SDK가 수집 |
| 보안 (XSS) | ❌ 호스트에서 조작 가능 | ✅ same-origin | ✅ iframe 격리 |
| 프로액티브 | ✅ 호스트 이벤트 활용 | ❌ 불가 | ✅ JS SDK가 처리 |

### v2.0 통신 프로토콜 (postMessage)
```
JS SDK (호스트)          iframe (채팅)
  │                        │
  │ {type:"init",apiKey}  │
  ├───────────────────────►│
  │                        │
  │ {type:"ready"}         │
  │◄───────────────────────┤
  │                        │
  │ {type:"open"}          │
  ├───────────────────────►│  ← 버블 클릭 시
  │                        │
  │ {type:"unread",count:3}│
  │◄───────────────────────┤  ← 미읽 배지 업데이트
  │                        │
  │ {type:"close"}         │
  ├───────────────────────►│  ← X 버튼 클릭 시
```

### 참고: 경쟁사 방식
- Intercom, 최신 채널톡: **하이브리드** (버블 JS + 채팅 iframe)
- Chatwoot, Tawk.to: **JS SDK만**
- Rocket.Chat: **iframe만**

---

## 6. 릴리스 전략

Phase 1~6 완료 후 v1.0 (JS SDK) 출시, 이후 v2.0 (하이브리드) 전환:

```
Phase 0: 문서 (Gate 0)
  ↓
Phase 1: 프로젝트 셋업
  ↓
Phase 2: API 마이그레이션 (36개)
  ↓
Phase 3: WebSocket (Reverb)
  ↓
Phase 4: Blade 프론트엔드
  ↓
Phase 5: 텔레그램 + 부가기능
  ↓
Phase 6: Docker + QA
  ↓
Go 서버 교체 게이트 통과 → 배포
```

### 병렬 가능 구간
- Phase 2 (API) + Phase 4 (Blade): Controller와 View 병렬 개발
- Phase 3 (WS) + Phase 5 (텔레그램): 독립적

---

## 7. 제약 조건

| 제약 | 설명 |
|------|------|
| 데이터 호환 | 기존 PG 스키마 + MongoDB 데이터와 100% 호환 |
| widget.js | JS 코드 최소 변경 (WS 프로토콜만 Echo.js로 전환) |
| URL 호환 | API 경로 동일 유지 (/api/rooms, /api/admin/* 등) |
| Self-Hosted | Docker Compose 3줄 설치 유지 |
| 의존성 | PG + MongoDB + Redis 3종만 (Go 버전과 동일) |
| 오픈소스 | MIT License, 공개 저장소 |

---

## 8. 오픈소스 전략

### 라이선스: MIT

| 라이선스 | SaaS 허용 | 수정 공개 의무 | 선택 이유 |
|---------|----------|--------------|----------|
| MIT | ✅ 허용 | ❌ 없음 | 최대 채택률, Chatwoot와 동일 |
| Apache 2.0 | ✅ 허용 | ❌ 없음 | 특허 보호 있으나 복잡 |
| AGPL | ✅ 허용 (수정 공개 필수) | ✅ 있음 | SaaS 사업 시 제약 |

**WHY MIT:**
- 채택 장벽 최소 (기업/개인 모두 자유롭게 사용)
- Chatwoot, LiveChat Widget 등 경쟁 프로젝트가 MIT 사용
- 오픈소스 커뮤니티 기여 유도에 가장 효과적
- 상업적 사용 허용으로 생태계 확장

**WHY NOT AGPL:**
- SaaS로 운영하는 사용자가 소스 공개 의무 → 채택 저하
- self-hosted 목적인 우리 프로젝트와 맞지 않음

### 필수 파일 구조

```
live-chat-laravel/
├── LICENSE                    # MIT License
├── README.md                  # 프로젝트 소개 + 설치 + 사용법
├── CONTRIBUTING.md            # 기여 가이드 (PR 규칙, 코딩 스타일)
├── CODE_OF_CONDUCT.md         # 행동 강령
├── CHANGELOG.md               # 버전별 변경 이력
├── SECURITY.md                # 보안 취약점 보고 절차
├── .github/
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md
│   │   └── feature_request.md
│   ├── PULL_REQUEST_TEMPLATE.md
│   └── workflows/
│       ├── ci.yml             # GitHub Actions CI (PHPUnit + Lint)
│       └── docker.yml         # Docker 이미지 빌드 + 푸시
├── docker-compose.yml
├── Dockerfile
├── .env.example
└── docs/
    ├── self-hosted-guide.md   # 설치 가이드
    ├── api-reference.md       # API 문서
    └── widget-integration.md  # 위젯 삽입 가이드
```

### README 구조

```markdown
# 💬 Live Chat Widget

Open-source live chat widget for your website.
One line of code. Real-time. Self-hosted.

[Demo](https://lchat.shaul.kr) · [Docs](https://wiki.shaul.kr/ko/projects/lchat) · [Report Bug](issues)

## Features
- 🚀 1줄 설치 (<script> 태그)
- ⚡ 실시간 WebSocket (Laravel Reverb)
- 📱 텔레그램 봇 연동
- 🏢 멀티테넌트
- 🌙 다크 모드
- 🌐 다국어 (ko/en)
- 🤖 FAQ 자동 응답
- 📊 통계 대시보드

## Quick Start
  cp .env.example .env
  docker compose up -d

## Tech Stack
Laravel 12 · PHP 8.4 · Reverb · PostgreSQL · MongoDB · Redis

## License
MIT
```

### GitHub Actions CI

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres: { image: postgres:18 }
      mongodb: { image: mongo:8 }
      redis: { image: redis:8 }
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: 8.4, extensions: mongodb }
      - run: composer install
      - run: php artisan test
```

### 버전 관리

- Semantic Versioning (v1.0.0)
- CHANGELOG.md 유지
- GitHub Releases + Docker Hub 이미지 태그

### 경쟁 오픈소스 비교

| 프로젝트 | 라이선스 | 스택 | Stars | 차별점 |
|---------|---------|------|-------|--------|
| Chatwoot | MIT | Ruby/Rails | 22k+ | 옴니채널 (이메일/SNS) |
| Rocket.Chat | MIT | Node.js | 41k+ | 팀 메시징 중심 |
| Tawk.to | Proprietary | - | - | 무료지만 비공개 |
| **Ours** | **MIT** | **Laravel/PHP** | - | **Laravel 생태계, 1줄 위젯, Self-hosted** |

### 차별화 포인트
1. **Laravel 생태계**: PHP 개발자가 바로 커스터마이징 가능
2. **1줄 위젯 SDK**: 프레임워크 독립적 임베드
3. **최소 의존성**: PG + MongoDB + Redis 3종만
4. **Built-in Auth**: Keycloak 없이 독립 운영
5. **벤치마킹 28기능**: 채널톡/센드버드 수준 UX
