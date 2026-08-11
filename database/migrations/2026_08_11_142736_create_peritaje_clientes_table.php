<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peritaje_clientes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('peritaje_id')->nullable();
            $table->string('nombre_cliente', 120)->nullable();
            $table->string('documento_cliente', 120)->nullable();
            $table->string('telefono_cliene', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peritaje_clientes');
    }
};
