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
            $table->decimal('slo_availability_target', 5, 2)->default(99.00)->after('latency_threshold_ms');
            $table->unsignedInteger('slo_latency_target_ms')->default(500)->after('slo_availability_target');
            $table->string('sla_status', 20)->default('Cumple')->after('slo_latency_target_ms');
            $table->decimal('error_budget_percentage', 5, 2)->default(100.00)->after('sla_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'slo_availability_target',
                'slo_latency_target_ms',
                'sla_status',
                'error_budget_percentage',
            ]);
        });
    }
};
