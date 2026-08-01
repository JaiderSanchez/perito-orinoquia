<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Historial de estados
        if (!Schema::hasTable('peritaje_historial_estados')) {
            Schema::create('peritaje_historial_estados', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->string('estado', 50);
                $table->foreignId('usuario_id')->nullable()->constrained('users');
                $table->string('comentario', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 2. Accesorios evaluados
        if (!Schema::hasTable('peritaje_accesorios')) {
            Schema::create('peritaje_accesorios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->foreignId('catalogo_accesorio_id')->constrained('catalogo_accesorios');
                $table->boolean('presente')->nullable();
                $table->string('seleccion', 60)->nullable();
                $table->boolean('danado')->default(false);
                $table->decimal('costo_reparacion', 14, 2)->nullable();
                $table->text('comentario_dano')->nullable();
                $table->timestamps();
                $table->unique(['peritaje_id', 'catalogo_accesorio_id'], 'peritaje_accesorio_unique');
            });
        }

        // 3. Daños externos / carrocería
        if (!Schema::hasTable('peritaje_danos_externos')) {
            Schema::create('peritaje_danos_externos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->foreignId('catalogo_pieza_id')->constrained('catalogo_piezas_carroceria');
                $table->string('tipo_hallazgo', 40)->default('NINGUNO');
                $table->smallInteger('micras')->nullable();
                $table->text('comentario')->nullable();
                $table->timestamps();
                $table->unique(['peritaje_id', 'catalogo_pieza_id'], 'peritaje_dano_ext_unique');
            });
        }

        // 4. Daños internos / cabina
        if (!Schema::hasTable('peritaje_danos_internos')) {
            Schema::create('peritaje_danos_internos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->foreignId('catalogo_zona_id')->constrained('catalogo_zonas_cabina');
                $table->string('estado', 40)->default('OPTIMO');
                $table->string('desgaste', 40)->default('NORMAL');
                $table->text('comentario')->nullable();
                $table->timestamps();
                $table->unique(['peritaje_id', 'catalogo_zona_id'], 'peritaje_dano_int_unique');
            });
        }

        // 5. Detalles técnicos
        if (!Schema::hasTable('peritaje_detalles_tecnicos')) {
            Schema::create('peritaje_detalles_tecnicos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->foreignId('catalogo_elemento_id')->constrained('catalogo_elementos_tecnicos');
                $table->boolean('danado')->default(false);
                $table->string('comentario', 255)->nullable();
                $table->decimal('costo', 14, 2)->nullable();
                $table->timestamps();
                $table->unique(['peritaje_id', 'catalogo_elemento_id'], 'peritaje_det_tec_unique');
            });
        }

        // 6. Sistemas mecánicos
        if (!Schema::hasTable('peritaje_sistemas_mecanicos')) {
            Schema::create('peritaje_sistemas_mecanicos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->foreignId('catalogo_sistema_id')->constrained('catalogo_sistemas_mecanicos');
                $table->string('estado', 40)->default('BUENO');
                $table->text('observaciones')->nullable();
                $table->timestamps();
                $table->unique(['peritaje_id', 'catalogo_sistema_id'], 'peritaje_sis_mec_unique');
            });
        }

        // 7. Compresión de cilindros
        if (!Schema::hasTable('peritaje_compresion_cilindros')) {
            Schema::create('peritaje_compresion_cilindros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->smallInteger('numero_cilindro');
                $table->smallInteger('presion_psi')->nullable();
                $table->unique(['peritaje_id', 'numero_cilindro'], 'peritaje_cilindro_unique');
            });
        }

        // 8. Archivos adjuntos y evidencias
        if (!Schema::hasTable('archivos')) {
            Schema::create('archivos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peritaje_id')->constrained('peritajes')->onDelete('cascade');
                $table->string('categoria', 60);
                $table->unsignedBigInteger('entidad_relacionada_id')->nullable();
                $table->string('nombre_original', 255);
                $table->string('mime_type', 100);
                $table->text('url');
                $table->integer('tamanio_bytes')->nullable();
                $table->foreignId('subido_por')->nullable()->constrained('users');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
        Schema::dropIfExists('peritaje_compresion_cilindros');
        Schema::dropIfExists('peritaje_sistemas_mecanicos');
        Schema::dropIfExists('peritaje_detalles_tecnicos');
        Schema::dropIfExists('peritaje_danos_internos');
        Schema::dropIfExists('peritaje_danos_externos');
        Schema::dropIfExists('peritaje_accesorios');
        Schema::dropIfExists('peritaje_historial_estados');
    }
};
