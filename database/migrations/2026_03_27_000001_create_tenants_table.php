<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('테넌트 고유 ID');
            $table->string('name', 100)->comment('테넌트 이름 (사이트명)');
            $table->string('domain', 255)->nullable()->comment('허용 도메인 (CORS 검증용)');
            $table->string('api_key', 64)->unique()->comment('위젯 인증용 API 키 (ck_live_ 접두사)');
            $table->jsonb('widget_config')->default('{}')->comment('위젯 설정 JSON');
            $table->bigInteger('telegram_chat_id')->nullable()->comment('텔레그램 알림 수신 채팅 ID');
            $table->text('auto_reply_message')->nullable()->comment('오프라인 자동 응답 메시지');
            $table->string('owner_id', 255)->comment('테넌트 소유자 ID');
            $table->boolean('is_active')->default(true)->comment('활성 상태');
            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');
            $table->index('domain');
            $table->index('is_active');
        });
        DB::statement("COMMENT ON TABLE tenants IS '테넌트 — 멀티테넌트 SaaS의 사이트 단위'");
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
