# LCHAT — Self-Hosted Live Chat Widget

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-8892BF.svg)](https://php.net)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20.svg)](https://laravel.com)

웹사이트에 실시간 채팅 위젯을 추가하는 오픈소스 SaaS입니다. `<script>` 한 줄로 설치하고, Docker Compose로 셀프 호스팅합니다.

## 주요 기능

- **실시간 채팅** — Laravel Reverb (WebSocket) 기반
- **관리자 대시보드** — 6탭 (채팅/테넌트/상담원/피드백/FAQ/통계)
- **멀티테넌트** — API 키 기반 테넌트 분리
- **위젯 SDK** — `<script>` 1줄 삽입으로 설치
- **모바일 대응** — 반응형 UI + iOS 전용 채팅 페이지
- **다크 모드** — 시스템 연동 + 수동 토글
- **파일 첨부** — 이미지/문서 업로드 (10MB 제한)
- **FAQ 자동 응답** — 키워드 매칭 봇
- **피드백** — 만족도 평가 (1~5점)
- **Self-Hosted** — 외부 의존성 없음 (PG + MongoDB + Redis만 필요)

## 기술 스택

| 구분 | 기술 |
|------|------|
| 백엔드 | Laravel 13, PHP 8.4 |
| WebSocket | Laravel Reverb (Pusher 프로토콜 호환) |
| DB | PostgreSQL 18 + MongoDB 8 |
| 캐시/큐 | Redis 8 |
| 프론트엔드 | Blade + Tailwind 4 + Alpine.js |
| 인프라 | Docker Compose (8개 서비스) |

## Self-Hosted 설치

### 요구사항

- Docker 24+ / Docker Compose v2+
- 2GB RAM 이상

### 1. 프로젝트 클론

```bash
git clone https://github.com/shaul-tech-org/live-chat-laravel.git
cd live-chat-laravel
```

### 2. 환경 변수 설정

```bash
cp .env.example .env
```

`.env` 파일을 열어 필수 항목을 수정합니다:

```env
# 앱 설정
APP_URL=https://your-domain.com

# PostgreSQL
DB_USERNAME=chatuser
DB_PASSWORD=강력한_비밀번호

# MongoDB
MONGO_USERNAME=chatuser
MONGO_PASSWORD=강력한_비밀번호

# Redis
REDIS_PASSWORD=강력한_비밀번호

# 관리자 로그인
ADMIN_EMAIL=admin@your-domain.com
ADMIN_PASSWORD=관리자_비밀번호

# Reverb (WebSocket)
REVERB_APP_KEY=랜덤_문자열
REVERB_APP_SECRET=랜덤_문자열
REVERB_HOST=your-domain.com
```

### 3. Docker Compose 실행

```bash
docker compose up -d
```

8개 서비스가 기동됩니다:

| 서비스 | 역할 | 포트 |
|--------|------|------|
| nginx | 리버스 프록시 (HTTP + WSS) | 80 |
| app | PHP-FPM (Laravel) | 9000 |
| reverb | WebSocket 서버 | 8080 |
| worker | 큐 처리 | - |
| scheduler | 스케줄러 | - |
| postgres | RDBMS | 5432 |
| mongodb | 문서 DB | 27017 |
| redis | 캐시/큐/세션 | 6379 |

### 4. 의존성 설치 + 초기 설정

```bash
# Composer 의존성 설치
docker exec lchat-app composer install --no-dev --optimize-autoloader

# APP_KEY 생성
docker exec lchat-app php artisan key:generate --force

# 데이터베이스 마이그레이션
docker exec lchat-app php artisan migrate --force
```

### 5. 접속 확인

- 관리자: `http://localhost/login`
- Health: `http://localhost/api/health`
- 데모: `http://localhost/demo`

## 위젯 설치 (웹사이트에 삽입)

### 1. 관리자에서 테넌트 생성

로그인 후 **테넌트** 탭에서 테넌트를 생성하고 API 키를 복사합니다.

### 2. 웹사이트에 스크립트 추가

```html
<script
  src="https://your-domain.com/js/widget.js"
  data-api-key="YOUR_API_KEY"
  data-reverb-key="YOUR_REVERB_KEY"
  data-reverb-host="your-domain.com"
  data-reverb-port="443">
</script>
```

## 개발 환경

```bash
# 의존성 설치
composer install
npm install

# 환경 설정
cp .env.example .env
cp .env.testing.example .env.testing
php artisan key:generate

# DB 마이그레이션
php artisan migrate

# 개발 서버
php artisan serve &
php artisan reverb:start &
npm run dev
```

### 테스트

```bash
# 전체 테스트 (229개)
php artisan test

# 특정 그룹
php artisan test tests/Feature/Security/
php artisan test tests/Feature/Web/
```

## 아키텍처

```
Route → Controller → Service → Repository → Model
           ↓            ↓
       FormRequest    Event (broadcast)
           ↓
       ApiResource → ApiResponse
```

- **FormRequest** 18개 (RequestDTO)
- **ApiResource** 8개 (ResponseDTO)
- **Repository** 8 인터페이스 + 8 구현체
- **Service** 13개
- **Controller** 20개 (thin controller)
- **Broadcasting Event** 7개
- **PHPUnit** 229개 테스트 (560 assertions)

## API 문서

| 그룹 | 엔드포인트 | 인증 |
|------|-----------|------|
| Public | `GET /api/health` | 없음 |
| Widget | `POST /api/rooms` | X-API-Key |
| Widget | `POST /api/rooms/{id}/messages` | X-API-Key |
| Widget | `POST /api/rooms/{id}/typing` | X-API-Key |
| Widget | `POST /api/rooms/{id}/reactions` | X-API-Key |
| Widget | `POST /api/rooms/{id}/read` | X-API-Key |
| Admin | `GET /api/admin/rooms` | Bearer Token |
| Admin | `POST /api/admin/tenants` | Bearer Token |
| Admin | `GET /api/admin/stats` | Bearer Token |
| Auth | `POST /api/auth/login` | 없음 |
| WS | `POST /api/broadcasting/auth` | X-API-Key / Bearer |

전체 API 명세는 [docs/api-spec.md](docs/api-spec.md)를 참조하세요.

## 라이선스

[MIT License](LICENSE)
