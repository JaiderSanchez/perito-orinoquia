<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('peritajes', function (Blueprint $table) {
        $table->string('traccion')->nullable(); // Por si acaso
        $table->string('compresion_motor')->nullable();
        $table->json('compresion_cilindros')->nullable();
        $table->boolean('fugas_aceite')->default(false);
        $table->string('estado_bateria')->nullable();
        $table->boolean('ruidos_extranos')->default(false);
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
