<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE notification_channels MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'Email'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notification_channels ALTER COLUMN type TYPE VARCHAR(50)');
        } else {
            Schema::table('notification_channels', function (Blueprint $table) {
                $table->string('type', 50)->default('Email')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE notification_channels MODIFY COLUMN type ENUM('Email', 'WhatsApp') NOT NULL DEFAULT 'Email'");
        } else {
            Schema::table('notification_channels', function (Blueprint $table) {
                $table->string('type', 50)->default('Email')->change();
            });
        }
    }
};
