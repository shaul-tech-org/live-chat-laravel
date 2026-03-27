<?php

/**
 * PHPUnit Test Bootstrap
 *
 * Docker 컨테이너 환경에서 OS 환경변수(PostgreSQL)가 테스트 설정을 덮어쓰는 문제 해결.
 * PHPUnit의 force="true"보다 먼저 실행되어 SQLite :memory: DB를 강제 적용.
 */

// 테스트용 DB 설정 강제 적용 (Docker 환경변수 오버라이드)
$testEnvOverrides = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_HOST' => '',
    'DB_PORT' => '',
    'DB_USERNAME' => '',
    'DB_PASSWORD' => '',
    'MONGODB_URI' => 'mongodb://localhost:27017',
    'MONGODB_DATABASE' => 'live_chat_test',
    'BROADCAST_CONNECTION' => 'log',
    'QUEUE_CONNECTION' => 'sync',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'REDIS_HOST' => '127.0.0.1',
    'REDIS_PASSWORD' => '',
];

foreach ($testEnvOverrides as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// .env.testing이 있으면 로드 (추가 설정)
if (file_exists(__DIR__ . '/../.env.testing')) {
    $lines = file(__DIR__ . '/../.env.testing', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // 이미 오버라이드된 값은 건너뜀
            if (!isset($testEnvOverrides[$key])) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

require __DIR__ . '/../vendor/autoload.php';
