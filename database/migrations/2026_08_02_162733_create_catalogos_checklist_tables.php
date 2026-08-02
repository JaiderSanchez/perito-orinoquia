<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_accesorios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tipo_vehiculo_id')->constrained('tipos_vehiculo')->cascadeOnDelete();
            $table->string('codigo', 40);
            $table->string('nombre', 120);
            $table->string('tipo_campo', 20)->default('booleano'); // booleano | seleccion_multiple
            $table->jsonb('opciones')->nullable();
            $table->string('valor_por_defecto', 60)->nullable();
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unique(['tipo_vehiculo_id', 'codigo']);
        });

        Schema::create('catalogo_piezas_carroceria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tipo_vehiculo_id')->constrained('tipos_vehiculo')->cascadeOnDelete();
            $table->string('codigo', 40);
            $table->string('nombre', 120);
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unique(['tipo_vehiculo_id', 'codigo']);
        });

        Schema::create('catalogo_zonas_cabina', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tipo_vehiculo_id')->constrained('tipos_vehiculo')->cascadeOnDelete();
            $table->string('codigo', 40);
            $table->string('nombre', 120);
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unique(['tipo_vehiculo_id', 'codigo']);
        });

        Schema::create('catalogo_elementos_tecnicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 120);
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
        });

        Schema::create('catalogo_sistemas_mecanicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tipo_vehiculo_id')->constrained('tipos_vehiculo')->cascadeOnDelete();
            $table->string('codigo', 40);
            $table->string('nombre', 160);
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unique(['tipo_vehiculo_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_sistemas_mecanicos');
        Schema::dropIfExists('catalogo_elementos_tecnicos');
        Schema::dropIfExists('catalogo_zonas_cabina');
        Schema::dropIfExists('catalogo_piezas_carroceria');
        Schema::dropIfExists('catalogo_accesorios');
    }
};
