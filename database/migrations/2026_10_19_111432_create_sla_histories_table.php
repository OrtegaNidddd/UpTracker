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
        Schema::create('sla_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->unsignedInteger('total_checks')->default(0);
            $table->unsignedInteger('up_checks')->default(0);
            $table->decimal('uptime_sli', 5, 2)->default(100.00);
            $table->unsignedInteger('avg_latency_ms')->default(0);
            $table->unsignedInteger('p95_latency_ms')->default(0);
            $table->decimal('latency_compliance_rate', 5, 2)->default(100.00);
            $table->decimal('error_budget_percentage', 5, 2)->default(100.00);
            $table->string('status', 20)->default('Cumple');
            $table->timestamps();

            $table->unique(['service_id', 'snapshot_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_histories');
    }
};
