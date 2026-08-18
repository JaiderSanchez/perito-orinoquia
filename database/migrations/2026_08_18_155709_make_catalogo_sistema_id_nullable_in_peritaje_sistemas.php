<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('peritaje_sistemas_mecanicos', function (Blueprint $table) {
        // 1. Eliminamos la llave foránea que causa el conflicto de tipos
        $table->dropForeign('peritaje_sistemas_mecanicos_catalogo_sistema_id_foreign');
    });

    Schema::table('peritaje_sistemas_mecanicos', function (Blueprint $table) {
        // 2. Hacemos que la columna acepte nulos
        $table->uuid('catalogo_sistema_id')->nullable()->change();
        // Nota: Mantenemos el tipo uuid o lo ajustamos si es necesario, pero con .nullable() bastará una vez quitada la llave foránea.
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peritaje_sistemas_mecanicos', function (Blueprint $table) {
            //
        });
    }
};
