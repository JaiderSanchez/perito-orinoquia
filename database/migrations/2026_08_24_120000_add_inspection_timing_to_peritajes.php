<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->unsignedInteger('tiempo_completitud_segundos')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            $table->dropColumn([
                'iniciado_en',
                'finalizado_en',
                'tiempo_completitud_segundos',
            ]);
        });
    }
};
