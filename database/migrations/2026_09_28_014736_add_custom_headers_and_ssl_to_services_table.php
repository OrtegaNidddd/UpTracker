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
        Schema::table('services', function (Blueprint $table) {
            $table->json('custom_headers')->nullable()->after('http_method');
            $table->timestamp('ssl_expires_at')->nullable()->after('is_active');
            $table->string('ssl_status', 20)->default('None')->after('ssl_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['custom_headers', 'ssl_expires_at', 'ssl_status']);
        });
    }
};
