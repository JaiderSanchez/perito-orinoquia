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
        $table->string('sistema_key')->nullable(); // O el tipo de dato que necesites
    });
}

public function down()
{
    Schema::table('peritaje_sistemas_mecanicos', function (Blueprint $table) {
        $table->dropColumn('sistema_key');
    });
}
};
