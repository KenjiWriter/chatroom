<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type')->default('text')->after('room_id');
            $table->json('metadata')->nullable()->after('content');
        });

        Schema::table('direct_messages', function (Blueprint $table) {
            $table->string('type')->default('text')->after('conversation_id');
            $table->json('metadata')->nullable()->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'metadata']);
        });

        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'metadata']);
        });
    }
};
