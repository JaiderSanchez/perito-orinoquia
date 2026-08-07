<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Esto permite que la columna acepte valores nulos en PostgreSQL
        DB::statement('ALTER TABLE peritaje_danos_externos ALTER COLUMN catalogo_pieza_id DROP NOT NULL;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE peritaje_danos_externos ALTER COLUMN catalogo_pieza_id SET NOT NULL;');
    }
};
