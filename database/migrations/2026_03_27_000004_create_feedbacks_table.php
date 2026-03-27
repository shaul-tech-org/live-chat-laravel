<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('피드백 고유 ID');
            $table->uuid('tenant_id')->comment('소속 테넌트 ID (약한 연결)');
            $table->uuid('room_id')->comment('채팅방 ID (약한 연결)');
            $table->string('visitor_email', 255)->nullable()->comment('방문자 이메일');
            $table->tinyInteger('rating')->comment('만족도 점수 (1~5)');
            $table->text('comment')->nullable()->comment('추가 의견');
            $table->text('page_url')->nullable()->comment('피드백 제출 시 페이지 URL');
            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');
            $table->index(['tenant_id', 'created_at']);
            $table->index('room_id');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("COMMENT ON TABLE feedbacks IS '피드백 — 채팅 종료 후 방문자 만족도 조사'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
