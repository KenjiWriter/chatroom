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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('banner_url')->nullable()->after('avatar_url');
            $table->text('bio')->nullable()->after('banner_url');
            $table->boolean('is_private')->default(false)->after('bio');
            $table->json('preferences')->nullable()->after('is_private');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_url', 'banner_url', 'bio', 'is_private', 'preferences']);
        });
    }
};
