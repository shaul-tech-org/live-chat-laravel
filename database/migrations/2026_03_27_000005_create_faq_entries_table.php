<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_entries', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('FAQ 고유 ID');
            $table->uuid('tenant_id')->comment('소속 테넌트 ID (약한 연결)');
            $table->string('keyword', 255)->comment('매칭 키워드');
            $table->text('answer')->comment('자동 응답 내용');
            $table->boolean('is_active')->default(true)->comment('활성 상태');
            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'keyword']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("COMMENT ON TABLE faq_entries IS 'FAQ 자동 응답 — 키워드 매칭 시 자동 답변 전송'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_entries');
    }
};
