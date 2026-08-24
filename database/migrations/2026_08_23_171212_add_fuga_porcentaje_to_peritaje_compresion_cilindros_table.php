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
        Schema::table('peritaje_compresion_cilindros', function (Blueprint $table) {
            if (!Schema::hasColumn('peritaje_compresion_cilindros', 'fuga_porcentaje')) {
                $table->decimal('fuga_porcentaje', 5, 2)->nullable()->after('presion_psi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peritaje_compresion_cilindros', function (Blueprint $table) {
            $table->dropColumn('fuga_porcentaje');
        });
    }
};
