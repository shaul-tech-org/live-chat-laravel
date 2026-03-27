<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('상담원 고유 ID');
            $table->uuid('tenant_id')->comment('소속 테넌트 ID (약한 연결)');
            $table->string('user_id', 255)->comment('인증 시스템 사용자 ID');
            $table->string('name', 100)->nullable()->comment('상담원 표시 이름');
            $table->string('email', 255)->nullable()->comment('상담원 이메일');
            $table->string('role', 20)->default('agent')->comment('역할 (admin/agent)');
            $table->boolean('is_online')->default(false)->comment('현재 온라인 여부');
            $table->boolean('is_active')->default(true)->comment('활성 상태');
            $table->timestamp('last_seen_at')->nullable()->comment('마지막 접속 시각');
            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'user_id']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("COMMENT ON TABLE agents IS '상담원 — 테넌트별 채팅 응대 담당자'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
