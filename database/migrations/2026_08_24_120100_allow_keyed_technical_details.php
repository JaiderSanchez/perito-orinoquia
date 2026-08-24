<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('peritaje_detalles_tecnicos', function (Blueprint $table) {
            $table->uuid('catalogo_elemento_id')->nullable()->change();
            $table->string('elemento_key', 80)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('peritaje_detalles_tecnicos', function (Blueprint $table) {
            $table->dropColumn('elemento_key');
            $table->uuid('catalogo_elemento_id')->nullable(false)->change();
        });
    }
};
