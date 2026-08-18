<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            $table->text('firma_inspector')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            $table->dropColumn('firma_inspector');
        });
    }
};
