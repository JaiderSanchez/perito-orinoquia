<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_accesorios', function (Blueprint $table) {
            // Agregamos el campo slug permitiendo nulos por si hay registros previos
            $table->string('slug')->nullable()->after('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_accesorios', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
