<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->string('categoria', 30); // SOAT|RTM|FIRMA_INSPECTOR|FOTO_DANO_EXTERNO|FOTO_DETALLE_TECNICO|FOTO_ACCESORIO|OTRO
            $table->uuid('entidad_relacionada_id')->nullable();
            $table->string('nombre_original', 255);
            $table->string('mime_type', 100);
            $table->text('url'); // ruta relativa en el disco "public" (storage/app/public/...)
            $table->integer('tamanio_bytes')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE archivos ADD CONSTRAINT chk_categoria_archivo CHECK (categoria IN
            ('SOAT','RTM','FIRMA_INSPECTOR','FOTO_DANO_EXTERNO','FOTO_DETALLE_TECNICO','FOTO_ACCESORIO','OTRO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
