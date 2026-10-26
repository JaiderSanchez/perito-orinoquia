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
        Schema::table('peritajes', function (Blueprint $table) {
            $table->json('evaluaciones_visuales')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            $table->dropColumn('evaluaciones_visuales');
        });
    }
};
