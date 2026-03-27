# Docker 인프라 계획 — LCHAT

> 작성일: 2026-03-27
> 목적: Self-hosted Docker Compose 환경 설계

---

## 1. 컨테이너 구성

```
docker/
├── nginx/
│   ├── Dockerfile          # Nginx 기반 이미지
│   └── default.conf        # 사이트 설정 (PHP-FPM + Reverb 프록시)
├── php/
│   ├── Dockerfile          # PHP 8.4-FPM + 확장 (pdo_pgsql, mongodb, redis, pcntl)
│   └── php.ini             # PHP 설정 (upload_max_filesize, timezone 등)
├── reverb/
│   └── (PHP 이미지 공유, entrypoint만 다름)
├── worker/
│   └── (PHP 이미지 공유, queue:work 실행)
└── scheduler/
    └── (PHP 이미지 공유, schedule:run 실행)
```

---

## 2. 서비스 목록

| 서비스 | 이미지 | 포트 | 역할 |
|--------|--------|------|------|
| **nginx** | nginx:alpine | 80, 443 | 리버스 프록시 (HTTP + WSS) |
| **app** | php:8.4-fpm (커스텀) | 9000 (내부) | Laravel PHP-FPM |
| **reverb** | (app 이미지 공유) | 8080 | WebSocket 서버 |
| **worker** | (app 이미지 공유) | - | Queue Worker (이벤트 처리) |
| **scheduler** | (app 이미지 공유) | - | Task Scheduler (cron) |
| **postgres** | postgres:18-alpine | 5432 | RDBMS |
| **mongodb** | mongo:8 | 27017 | NoSQL (메시지/이벤트) |
| **redis** | redis:8-alpine | 6379 | Cache/Pub/Sub/Queue/Rate |

**총 8개 서비스** (nginx + app + reverb + worker + scheduler + PG + MongoDB + Redis)

---

## 3. PHP Dockerfile

```dockerfile
# docker/php/Dockerfile
FROM php:8.4-fpm-alpine

# 시스템 의존성
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP 확장
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    pcntl \
    sockets

# MongoDB 확장
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Redis 확장
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 소스 복사
COPY . .
RUN composer install --no-dev --optimize-autoloader

# 퍼미션
RUN chown -R www-data:www-data storage bootstrap/cache

# PHP 설정
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

EXPOSE 9000
CMD ["php-fpm"]
```

### PHP 확장 목록

| 확장 | 용도 | 필수 |
|------|------|------|
| pdo_pgsql | PostgreSQL 연결 | ✅ |
| mongodb | MongoDB 연결 | ✅ |
| redis | Redis 연결 | ✅ |
| pcntl | Reverb/Queue Worker (시그널 처리) | ✅ |
| sockets | Reverb WebSocket | ✅ |
| zip | Composer 의존성 | ✅ |
| intl | 다국어 지원 | ✅ |

---

## 4. Nginx 설정

```nginx
# docker/nginx/default.conf
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    client_max_body_size 10M;

    # Laravel HTTP
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Reverb WebSocket
    location /app {
        proxy_pass http://reverb:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
    }

    # Broadcasting auth (Reverb 채널 인증)
    location /broadcasting {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 정적 파일 캐시
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1h;
        add_header Cache-Control "public, immutable";
    }

    # 업로드 파일
    location /storage {
        alias /var/www/html/storage/app/public;
    }

    # 보안 헤더
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # .env 등 숨김 파일 차단
    location ~ /\. {
        deny all;
    }
}
```

---

## 5. docker-compose.yml 구조

```yaml
version: "3.8"

services:
  # ===== Web Server =====
  nginx:
    build: ./docker/nginx
    ports:
      - "${APP_PORT:-80}:80"
    volumes:
      - ./public:/var/www/html/public:ro
      - ./storage/app/public:/var/www/html/storage/app/public:ro
    depends_on:
      - app
      - reverb
    restart: unless-stopped

  # ===== PHP Application =====
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
    env_file: .env
    depends_on:
      postgres:
        condition: service_healthy
      mongodb:
        condition: service_healthy
      redis:
        condition: service_healthy
    restart: unless-stopped

  # ===== WebSocket Server =====
  reverb:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
    volumes:
      - .:/var/www/html
    env_file: .env
    depends_on:
      - redis
    restart: unless-stopped

  # ===== Queue Worker =====
  worker:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
    volumes:
      - .:/var/www/html
    env_file: .env
    depends_on:
      - redis
    restart: unless-stopped

  # ===== Task Scheduler =====
  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: sh -c "while true; do php artisan schedule:run --no-interaction; sleep 60; done"
    volumes:
      - .:/var/www/html
    env_file: .env
    depends_on:
      - app
    restart: unless-stopped

  # ===== PostgreSQL 18 =====
  postgres:
    image: postgres:18-alpine
    environment:
      POSTGRES_USER: ${DB_USERNAME:-chatuser}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-changeme}
      POSTGRES_DB: ${DB_DATABASE:-live_chat}
    volumes:
      - postgres-data:/var/lib/postgresql/data
    ports:
      - "${PG_PORT:-5432}:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-chatuser}"]
      interval: 5s
      timeout: 3s
      retries: 5
    restart: unless-stopped

  # ===== MongoDB 8 =====
  mongodb:
    image: mongo:8
    environment:
      MONGO_INITDB_ROOT_USERNAME: ${MONGO_USERNAME:-chatuser}
      MONGO_INITDB_ROOT_PASSWORD: ${MONGO_PASSWORD:-changeme}
    volumes:
      - mongodb-data:/data/db
    ports:
      - "${MONGO_PORT:-27017}:27017"
    healthcheck:
      test: ["CMD", "mongosh", "--eval", "db.adminCommand('ping')", "-u", "${MONGO_USERNAME:-chatuser}", "-p", "${MONGO_PASSWORD:-changeme}", "--authenticationDatabase", "admin"]
      interval: 5s
      timeout: 3s
      retries: 5
    restart: unless-stopped

  # ===== Redis 8 =====
  redis:
    image: redis:8-alpine
    command: redis-server --requirepass ${REDIS_PASSWORD:-changeme}
    volumes:
      - redis-data:/data
    ports:
      - "${REDIS_PORT:-6379}:6379"
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD:-changeme}", "ping"]
      interval: 5s
      timeout: 3s
      retries: 5
    restart: unless-stopped

volumes:
  postgres-data:
  mongodb-data:
  redis-data:

networks:
  default:
    name: lchat-network
```

---

## 6. 서비스 역할 상세

```
                    ┌─── 외부 요청 (HTTP/WSS) ───┐
                    ▼                             │
              ┌─── nginx (:80) ──────────────────┐
              │                                   │
              │  / → PHP-FPM (app:9000)          │
              │  /app → Reverb (reverb:8080)     │
              │  /broadcasting → PHP-FPM          │
              │  /storage → 정적 파일              │
              └───────────────────────────────────┘
                    │               │
          ┌────────▼──┐    ┌──────▼──────┐
          │ app (FPM) │    │   reverb    │
          │           │    │ (WS 서버)   │
          │ Controller│    │ Broadcasting│
          │ Eloquent  │    │ Channels    │
          │ Services  │◄──►│             │
          └──┬──┬──┬──┘    └─────────────┘
             │  │  │           │
    ┌────────┘  │  └────────┐  │
    ▼           ▼           ▼  ▼
┌────────┐ ┌────────┐ ┌────────┐
│ PG 18  │ │Mongo 8 │ │Redis 8 │
│        │ │        │ │        │
│ 5 table│ │ 4 coll │ │pub/sub │
│ ACID   │ │ TTL    │ │queue   │
│ Eloquent│ │message│ │cache   │
└────────┘ └────────┘ └────────┘

          ┌────────┐  ┌───────────┐
          │ worker │  │ scheduler │
          │ queue  │  │ cron 60s  │
          │ :work  │  │ schedule  │
          └────────┘  │ :run      │
                      └───────────┘
```

---

## 7. PHP.ini 설정

```ini
# docker/php/php.ini
[PHP]
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 60

[Date]
date.timezone = Asia/Seoul

[opcache]
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
```

---

## 8. 의사결정

### WHY nginx + PHP-FPM (분리) vs Apache + mod_php (통합)?
| 항목 | nginx + FPM | Apache + mod_php |
|------|:-----------:|:----------------:|
| 성능 | ✅ 높음 (비동기) | 중간 |
| 메모리 | ✅ 적음 | 많음 |
| WS 프록시 | ✅ 네이티브 | 복잡 |
| 정적 파일 | ✅ 빠름 | 느림 |
| 설정 유연성 | ✅ 높음 | 중간 |

### WHY 4개 PHP 프로세스 (app + reverb + worker + scheduler)?
- **app**: HTTP 요청 처리 (PHP-FPM pool)
- **reverb**: WebSocket 상시 연결 (별도 프로세스 필수)
- **worker**: Queue 비동기 처리 (Broadcasting, 텔레그램, 이메일)
- **scheduler**: 스케줄 작업 (MongoDB TTL 정리, 통계 집계 등)

### WHY 같은 PHP 이미지 공유?
- 코드베이스 동일 → 이미지 빌드 1회
- entrypoint/command만 다름 → 메모리 효율
- 의존성(확장) 동일 → 관리 단순

---

## 9. 파일 구조 (최종)

```
docker/
├── nginx/
│   ├── Dockerfile          # FROM nginx:alpine + COPY conf
│   └── default.conf        # 사이트 설정
├── php/
│   ├── Dockerfile          # FROM php:8.4-fpm-alpine + 확장
│   └── php.ini             # PHP 설정
docker-compose.yml          # 8개 서비스 정의
.env.example                # 환경 변수 템플릿
```
