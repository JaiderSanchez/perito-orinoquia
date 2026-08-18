<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peritaje_imagenes', function (Blueprint $table) {
            $table->id();

            // Relación con la tabla de peritajes
            $table->unsignedBigInteger('peritaje_id');
            $table->foreign('peritaje_id')->references('id')->on('peritajes')->onDelete('cascade');

            // Seccion y item para identificar a qué parte de la inspección pertenece la imagen (SOAT, RTM, etc.)
            $table->string('seccion');
            $table->string('item_id')->nullable();

            // Almacenamiento de la imagen en Base64
            $table->text('imagen_base64');
            $table->string('nombre_archivo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peritaje_imagenes');
    }
};
