<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear usuario de prueba si lo necesitas
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'), // Asegura una contraseña si es requerido
            ]
        );

        // 2. Llamar al seeder de catálogos de peritaje
        $this->call([
            PeritajeCatalogoSeeder::class,
        ]);
    }
}
