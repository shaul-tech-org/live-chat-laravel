<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->string('assignment_method', 10)
                ->nullable()
                ->after('assigned_agent_id')
                ->comment('배정 방식 (auto/manual)');
        });
    }

    public function down(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropColumn('assignment_method');
        });
    }
};
