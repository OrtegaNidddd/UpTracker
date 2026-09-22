<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('latency_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->integer('latency_ms')->nullable();
            $table->integer('http_status_code')->nullable();
            $table->enum('status', ['Up', 'Down']);
            $table->timestamp('checked_at')->useCurrent();
            
            // Índice optimizado para las gráficas del Dashboard
            $table->index(['service_id', 'checked_at'], 'idx_latency_service_checked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('latency_logs');
    }
};
