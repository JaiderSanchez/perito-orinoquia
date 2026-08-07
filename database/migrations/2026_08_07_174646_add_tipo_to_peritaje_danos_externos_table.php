<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('peritaje_danos_externos', function (Blueprint $table) {
            $table->uuid('catalogo_pieza_carroceria_id')->nullable();

            // Opcional: Asegúrate de que las demás columnas del array también existan por si acaso
            if (!Schema::hasColumn('peritaje_danos_externos', 'micras')) {
                $table->integer('micras')->nullable();
            }
            if (!Schema::hasColumn('peritaje_danos_externos', 'comentario')) {
                $table->text('comentario')->nullable();
            }
            if (!Schema::hasColumn('peritaje_danos_externos', 'foto')) {
                $table->text('foto')->nullable();
            }
            if (!Schema::hasColumn('peritaje_danos_externos', 'fotoNombre')) {
                $table->string('fotoNombre')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peritaje_danos_externos', function (Blueprint $table) {
            $table->dropColumn([
                'catalogo_pieza_carroceria_id',
                'micras',
                'comentario',
                'foto',
                'fotoNombre'
            ]);
        });
    }
};
