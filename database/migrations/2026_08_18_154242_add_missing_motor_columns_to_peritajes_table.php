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
    Schema::table('peritajes', function (Blueprint $table) {
        if (!Schema::hasColumn('peritajes', 'fugas_aceite')) {
            $table->boolean('fugas_aceite')->default(false);
        }
        if (!Schema::hasColumn('peritajes', 'estado_bateria')) {
            $table->string('estado_bateria')->nullable();
        }
        if (!Schema::hasColumn('peritajes', 'ruidos_extranos')) {
            $table->boolean('ruidos_extranos')->default(false);
        }
        if (!Schema::hasColumn('peritajes', 'compresion_motor')) {
            $table->string('compresion_motor')->nullable();
        }
        if (!Schema::hasColumn('peritajes', 'compresion_cilindros')) {
            $table->json('compresion_cilindros')->nullable();
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            //
        });
    }
};
