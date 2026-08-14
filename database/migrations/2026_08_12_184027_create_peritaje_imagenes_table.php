<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peritaje_imagenes', function (Blueprint $table) {
            $table->id();
            $table->uuid('peritaje_id'); // Llave foránea a la tabla peritajes

            $table->string('seccion'); // 'documentacion_soat', 'vista_externa', 'detalles_tecnicos', 'firma_digital', etc.
            $table->string('item_id')->nullable(); // Identificador interno de la pieza, componente o elemento

            $table->longText('imagen_base64'); // Almacena la cadena Base64 completa
            $table->string('nombre_archivo')->nullable();

            $table->timestamps();

            // Relación con peritajes
            $table->foreign('peritaje_id')->references('id')->on('peritajes')->onDelete('cascade');

            // Índice único para asegurar que una misma sección o pieza no se duplique, sino que se actualice
            $table->unique(['peritaje_id', 'seccion', 'item_id'], 'uq_peritaje_imagen_seccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peritaje_imagenes');
    }
};
