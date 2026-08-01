<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tipos de vehículo
        if (!Schema::hasTable('tipos_vehiculo')) {
            Schema::create('tipos_vehiculo', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 20)->unique();
                $table->string('nombre', 60);
                $table->string('icono', 10)->nullable();
                $table->string('descripcion', 160)->nullable();
                $table->smallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
            });
        }

        // 2. Sucursales
        if (!Schema::hasTable('sucursales')) {
            Schema::create('sucursales', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 120);
                $table->string('ciudad', 80)->nullable();
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        // 3. Vendedores
        if (!Schema::hasTable('vendedores')) {
            Schema::create('vendedores', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 150);
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->onDelete('set null');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 4. Catálogo de Accesorios
        if (!Schema::hasTable('catalogo_accesorios')) {
            Schema::create('catalogo_accesorios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo')->onDelete('cascade');
                $table->string('codigo', 40);
                $table->string('nombre', 120);
                $table->string('tipo_campo', 40)->default('booleano');
                $table->jsonb('opciones')->nullable();
                $table->string('valor_por_defecto', 60)->nullable();
                $table->smallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->unique(['tipo_vehiculo_id', 'codigo']);
            });
        }

        // 5. Catálogo de Piezas de Carrocería
        if (!Schema::hasTable('catalogo_piezas_carroceria')) {
            Schema::create('catalogo_piezas_carroceria', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo')->onDelete('cascade');
                $table->string('codigo', 40);
                $table->string('nombre', 120);
                $table->smallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->unique(['tipo_vehiculo_id', 'codigo']);
            });
        }

        // 6. Catálogo de Zonas de Cabina
        if (!Schema::hasTable('catalogo_zonas_cabina')) {
            Schema::create('catalogo_zonas_cabina', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo')->onDelete('cascade');
                $table->string('codigo', 40);
                $table->string('nombre', 120);
                $table->smallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->unique(['tipo_vehiculo_id', 'codigo']);
            });
        }

        // 7. Catálogo de Elementos Técnicos
        if (!Schema::hasTable('catalogo_elementos_tecnicos')) {
            Schema::create('catalogo_elementos_tecnicos', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 40)->unique();
                $table->string('nombre', 120);
                $table->smallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
            });
        }

        // 8. Catálogo de Sistemas Mecánicos
        if (!Schema::hasTable('catalogo_sistemas_mecanicos')) {
            Schema::create('catalogo_sistemas_mecanicos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo')->onDelete('cascade');
                $table->string('codigo', 40);
                $table->string('nombre', 160);
                $table->smallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->unique(['tipo_vehiculo_id', 'codigo']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_sistemas_mecanicos');
        Schema::dropIfExists('catalogo_elementos_tecnicos');
        Schema::dropIfExists('catalogo_zonas_cabina');
        Schema.dropIfExists('catalogo_piezas_carroceria');
        Schema::dropIfExists('catalogo_accesorios');
        Schema::dropIfExists('vendedores');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('tipos_vehiculo');
    }
};
