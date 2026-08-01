<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoVehiculoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tipos_vehiculo')->insert([
            ['codigo' => 'carro', 'nombre' => 'Carro / Automóvil', 'icono' => '🚗', 'orden' => 1],
            ['codigo' => 'moto', 'nombre' => 'Moto', 'icono' => '🏍️', 'orden' => 2],
            ['codigo' => 'pesado', 'nombre' => 'Vehículo Pesado', 'icono' => '🚛', 'orden' => 3],
            ['codigo' => 'motocarro', 'nombre' => 'Motocarro', 'icono' => '🛺', 'orden' => 4],
        ]);
    }
}
