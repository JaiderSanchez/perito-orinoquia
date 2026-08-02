<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_vehiculo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 20)->unique(); // carro | moto | pesado | motocarro
            $table->string('nombre', 60);
            $table->string('icono', 10)->nullable();
            $table->string('descripcion', 160)->nullable();
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
        });

        Schema::create('sucursales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('ciudad', 80)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('vendedores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 150);
            $table->foreignUuid('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Nota: la autenticación ya la manejas con la tabla "users" que trae
        // Laravel por defecto. Aquí solo le agregamos lo que necesita el
        // dominio de peritajes: rol y sucursal a la que pertenece el usuario.
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'rol')) {
                $table->string('rol', 20)->default('inspector')->after('email'); // admin | supervisor | inspector
            }
            if (!Schema::hasColumn('users', 'sucursal_id')) {
                $table->foreignUuid('sucursal_id')->nullable()->after('rol')->constrained('sucursales')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'activo')) {
                $table->boolean('activo')->default(true)->after('sucursal_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'sucursal_id', 'activo']);
        });
        Schema::dropIfExists('vendedores');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('tipos_vehiculo');
    }
};
