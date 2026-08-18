<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('peritajes_sistemas_mecanicos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('peritaje_id')->constrained('peritajes')->onDelete('cascade');

            $table->string('item_key');
            $table->string('estado')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Revertir las migraciones (eliminar lo creado).
     */
    public function down(): void
    {
        Schema::dropIfExists('peritajes_sistemas_mecanicos');
    }
};
