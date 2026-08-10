<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Secuencia atómica para el consecutivo PER-0001, PER-0002... (evita
        // condiciones de carrera si dos inspectores crean un peritaje a la vez).
        DB::statement('CREATE SEQUENCE IF NOT EXISTS peritajes_codigo_seq START 1');

        Schema::create('peritajes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 20)->unique();

            $table->foreignUuid('tipo_vehiculo_id')->constrained('tipos_vehiculo');
            $table->string('estado', 20)->default('borrador'); // borrador|en_proceso|completado|anulado

            $table->foreignId('inspector_id')->constrained('users');
            $table->foreignUuid('sucursal_vendedor_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignUuid('sucursal_inspeccion_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignUuid('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();

            $table->timestampTz('fecha_peritaje')->useCurrent();

            // Identificación del vehículo
            $table->string('placa', 10);
            $table->string('marca', 80);
            $table->string('linea', 80);
            $table->smallInteger('modelo_anio');
            $table->string('num_motor', 60);
            $table->string('num_chasis', 60);
            $table->string('organismo_transito', 120)->nullable();
            $table->integer('kilometraje')->nullable();
            $table->string('tarjeta_operacion', 60)->nullable();
            $table->string('configuracion_ejes', 60)->nullable();
            $table->integer('color', 50)->nullable();

            // Documentación legal
            $table->string('numero_soat', 60)->nullable();
            $table->string('entidad_emisora_soat', 120)->nullable();
            $table->date('vence_soat')->nullable();
            $table->boolean('soat_al_dia')->default(true);

            $table->string('numero_control_rtm', 60)->nullable();
            $table->string('cda_emisor', 120)->nullable();
            $table->date('vence_tecnico_mecanica')->nullable();
            $table->boolean('tecnico_mecanica_al_dia')->default(true);

            $table->boolean('coincide_propietario_runt')->default(true);
            $table->boolean('tiene_embargos_o_alertas')->default(false);
            $table->string('restriccion_blindaje', 40)->default('sin_blindaje');
            $table->text('comentarios_siniestros')->nullable();

            // Motor (parámetros globales)
            $table->string('tipo_transmision', 40)->nullable();
            $table->string('estado_transmision', 40)->nullable();
            $table->text('comentarios_motor')->nullable();
            $table->smallInteger('porcentaje_bateria')->nullable();
            $table->string('vida_util_bateria', 120)->nullable();

            // Costos
            $table->decimal('costo_alistamiento', 14, 2)->default(0);
            $table->decimal('costo_reparacion', 14, 2)->default(0);
            $table->string('tiempo_estimado_reparacion', 40)->nullable();

            // Concepto final / puntajes
            $table->string('estado_general_vehiculo', 40)->default('Aceptable');
            $table->text('concepto_final')->nullable();
            $table->text('comentarios_generales')->nullable();
            $table->smallInteger('score_estructura')->default(100);
            $table->smallInteger('score_carroceria')->default(100);
            $table->smallInteger('score_mecanica')->default(100);
            $table->smallInteger('score_electrico')->default(100);
            $table->smallInteger('score_legal')->default(100);

            $table->timestampTz('firmado_en')->nullable();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE peritajes ADD CONSTRAINT chk_scores CHECK (
            score_estructura BETWEEN 0 AND 100 AND score_carroceria BETWEEN 0 AND 100 AND
            score_mecanica BETWEEN 0 AND 100 AND score_electrico BETWEEN 0 AND 100 AND
            score_legal BETWEEN 0 AND 100
        )');
        DB::statement('ALTER TABLE peritajes ADD CONSTRAINT chk_porcentaje_bateria CHECK (porcentaje_bateria IS NULL OR porcentaje_bateria BETWEEN 0 AND 100)');
        DB::statement("ALTER TABLE peritajes ADD CONSTRAINT chk_estado CHECK (estado IN ('borrador','en_proceso','completado','anulado'))");

        Schema::create('peritaje_historial_estados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peritaje_id')->constrained('peritajes')->cascadeOnDelete();
            $table->string('estado', 20);
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comentario', 255)->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peritaje_historial_estados');
        Schema::dropIfExists('peritajes');
        DB::statement('DROP SEQUENCE IF EXISTS peritajes_codigo_seq');
    }
};
