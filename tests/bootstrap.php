<?php

/**
 * PHPUnit Test Bootstrap
 *
 * CI(GitHub Actions)에서는 PostgreSQL, 로컬에서는 .env.testing 설정을 사용.
 * DB_CONNECTION 환경변수가 이미 설정되어 있으면 오버라이드하지 않음.
 */

$testEnvDefaults = [
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:rGTWn3qE1sxideY8NfUA1Oz/mxOk/M+tVikVgRWqc3Y=',
    'BROADCAST_CONNECTION' => 'log',
    'QUEUE_CONNECTION' => 'sync',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
];

foreach ($testEnvDefaults as $key => $value) {
    // 이미 설정된 환경변수는 건너뜀 (CI에서 설정한 값 존중)
    if (!getenv($key) || getenv($key) === '') {
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

require __DIR__ . '/../vendor/autoload.php';
