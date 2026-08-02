<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peritaje_accesorios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->foreignUuid('catalogo_accesorio_id')->constrained('catalogo_accesorios');
            $table->boolean('presente')->nullable();
            $table->string('seleccion', 60)->nullable();
            $table->boolean('danado')->default(false);
            $table->decimal('costo_reparacion', 14, 2)->nullable();
            $table->text('comentario_dano')->nullable();
            $table->timestamps();
            $table->unique(['peritaje_id', 'catalogo_accesorio_id'], 'uq_peritaje_accesorio');
        });

        Schema::create('peritaje_danos_externos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->foreignUuid('catalogo_pieza_id')->constrained('catalogo_piezas_carroceria');
            $table->string('tipo_hallazgo', 20)->default('NINGUNO'); // NINGUNO|RAYON|ABOLLADURA|GOLPE|REPINTADO
            $table->smallInteger('micras')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();
            $table->unique(['peritaje_id', 'catalogo_pieza_id'], 'uq_peritaje_pieza');
        });

        Schema::create('peritaje_danos_internos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->foreignUuid('catalogo_zona_id')->constrained('catalogo_zonas_cabina');
            $table->string('estado', 20)->default('OPTIMO'); // OPTIMO|REGULAR|DANADO
            $table->string('desgaste', 20)->default('NORMAL'); // MINIMO|NORMAL|ACELERADO
            $table->text('comentario')->nullable();
            $table->timestamps();
            $table->unique(['peritaje_id', 'catalogo_zona_id'], 'uq_peritaje_zona');
        });

        Schema::create('peritaje_detalles_tecnicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->foreignUuid('catalogo_elemento_id')->constrained('catalogo_elementos_tecnicos');
            $table->boolean('danado')->default(false);
            $table->string('comentario', 255)->nullable();
            $table->decimal('costo', 14, 2)->nullable();
            $table->timestamps();
            $table->unique(['peritaje_id', 'catalogo_elemento_id'], 'uq_peritaje_elemento');
        });

        Schema::create('peritaje_sistemas_mecanicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->foreignUuid('catalogo_sistema_id')->constrained('catalogo_sistemas_mecanicos');
            $table->string('estado', 20)->default('BUENO'); // BUENO|REGULAR|MALO
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['peritaje_id', 'catalogo_sistema_id'], 'uq_peritaje_sistema');
        });

        Schema::create('peritaje_compresion_cilindros', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->smallInteger('numero_cilindro');
            $table->smallInteger('presion_psi')->nullable();
            $table->unique(['peritaje_id', 'numero_cilindro'], 'uq_peritaje_cilindro');
        });

        DB::statement("ALTER TABLE peritaje_danos_externos ADD CONSTRAINT chk_tipo_hallazgo CHECK (tipo_hallazgo IN ('NINGUNO','RAYON','ABOLLADURA','GOLPE','REPINTADO'))");
        DB::statement("ALTER TABLE peritaje_danos_internos ADD CONSTRAINT chk_estado_cabina CHECK (estado IN ('OPTIMO','REGULAR','DANADO'))");
        DB::statement("ALTER TABLE peritaje_danos_internos ADD CONSTRAINT chk_desgaste_cabina CHECK (desgaste IN ('MINIMO','NORMAL','ACELERADO'))");
        DB::statement("ALTER TABLE peritaje_sistemas_mecanicos ADD CONSTRAINT chk_estado_mecanico CHECK (estado IN ('BUENO','REGULAR','MALO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('peritaje_compresion_cilindros');
        Schema::dropIfExists('peritaje_sistemas_mecanicos');
        Schema::dropIfExists('peritaje_detalles_tecnicos');
        Schema::dropIfExists('peritaje_danos_internos');
        Schema::dropIfExists('peritaje_danos_externos');
        Schema::dropIfExists('peritaje_accesorios');
    }
};
