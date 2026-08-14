<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check'
        );

        DB::statement(
            "ALTER TABLE users
             ADD CONSTRAINT users_rol_check
             CHECK (rol IN ('admin', 'tecnico', 'inspector'))"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check'
        );

        DB::statement(
            "ALTER TABLE users
             ADD CONSTRAINT users_rol_check
             CHECK (rol IN ('admin', 'tecnico'))"
        );
    }
};
