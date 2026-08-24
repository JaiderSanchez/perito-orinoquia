<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            if (!Schema::hasColumn('peritajes', 'fugas_aceite')) {
                $table->boolean('fugas_aceite')
                    ->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('peritajes', function (Blueprint $table) {
            if (Schema::hasColumn('peritajes', 'fugas_aceite')) {
                $table->dropColumn('fugas_aceite');
            }
        });
    }
};
