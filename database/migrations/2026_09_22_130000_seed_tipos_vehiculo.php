<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_vehiculo')->upsert([
            [
                'id' => '1c9740ed-b045-4643-9fe6-cfb2c412854f',
                'codigo' => 'carro',
                'nombre' => 'Carro / Automóvil',
                'icono' => 'car',
                'descripcion' => 'Livianos, sedán, SUV y camperos',
                'orden' => 1,
                'activo' => true,
            ],
            [
                'id' => '7c68a26d-372b-42dc-be00-92c4ed2ee6ce',
                'codigo' => 'moto',
                'nombre' => 'Moto',
                'icono' => 'bike',
                'descripcion' => 'Motocicletas de cilindrada variada',
                'orden' => 2,
                'activo' => true,
            ],
            [
                'id' => 'd5017832-04ac-4ead-8f57-efbe8af78860',
                'codigo' => 'pesado',
                'nombre' => 'Vehículo Pesado',
                'icono' => 'truck',
                'descripcion' => 'Camiones, tractocamiones y autobuses',
                'orden' => 3,
                'activo' => true,
            ],
            [
                'id' => 'e8ca5ff6-fe17-4916-b949-c13cac3a706e',
                'codigo' => 'motocarro',
                'nombre' => 'Motocarro',
                'icono' => 'car-front',
                'descripcion' => 'Tricimotos de carga o pasajeros',
                'orden' => 4,
                'activo' => true,
            ],
        ], ['id'], ['codigo', 'nombre', 'icono', 'descripcion', 'orden', 'activo']);
    }

    public function down(): void
    {
        // Se conservan estos registros de dominio para no invalidar peritajes existentes.
    }
};
