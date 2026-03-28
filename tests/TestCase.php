<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * MongoDB 연결이 불가능하면 테스트를 건너뛴다.
     */
    protected function skipIfNoMongo(): void
    {
        try {
            \App\Models\Mongo\Message::query()->count();
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            $this->markTestSkipped('MongoDB 연결 불가 — 테스트 환경에서 생략');
        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            $this->markTestSkipped('MongoDB 인증 실패 — 테스트 환경에서 생략');
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            $this->markTestSkipped('MongoDB 사용 불가 — ' . $e->getMessage());
        }
    }
}
