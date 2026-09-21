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
            $table->integer('response_time_ms')->nullable(); // Latencia en ms (nulo si dio timeout)
            $table->integer('status_code')->default(0);       // Código HTTP (200, 404, 500, etc.)
            $table->timestamps();

            // Índice compuesto para acelerar las consultas de los gráficos de Chart.js
            $table->index(['service_id', 'created_at']);
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
