<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('채팅방 고유 ID');
            $table->uuid('tenant_id')->comment('소속 테넌트 ID (약한 연결)');
            $table->string('visitor_id', 64)->comment('방문자 식별자');
            $table->string('visitor_name', 100)->nullable()->comment('방문자 이름');
            $table->string('visitor_email', 255)->nullable()->comment('방문자 이메일');
            $table->string('status', 20)->default('open')->comment('상태 (open/closed)');
            $table->uuid('assigned_agent_id')->nullable()->comment('담당 상담원 ID (약한 연결)');
            $table->timestamps();
            $table->timestamp('closed_at')->nullable()->comment('채팅 종료 시각');
            $table->softDeletes()->comment('소프트 삭제 시각');
            $table->index(['tenant_id', 'status']);
            $table->index('visitor_id');
            $table->index('assigned_agent_id');
        });
        DB::statement("COMMENT ON TABLE chat_rooms IS '채팅방 — 방문자와 상담원 간 대화 세션'");
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
