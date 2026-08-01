<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeritajeCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tipos de Vehículo
        $tipoCarroId = DB::table('tipos_vehiculo')->updateOrInsert(
            ['codigo' => 'carro'],
            ['nombre' => 'Carro / Automóvil', 'icono' => '🚗', 'orden' => 1, 'activo' => true]
        );
        $tipoCarroId = DB::table('tipos_vehiculo')->where('codigo', 'carro')->value('id');

        $tipoMotoId = DB::table('tipos_vehiculo')->updateOrInsert(
            ['codigo' => 'moto'],
            ['nombre' => 'Moto', 'icono' => '🏍️', 'orden' => 2, 'activo' => true]
        );
        $tipoMotoId = DB::table('tipos_vehiculo')->where('codigo', 'moto')->value('id');


        // 2. Catálogo de Accesorios (Ejemplo para Carro)
        $accesoriosCarro = [
            ['codigo' => 'gato', 'nombre' => 'Gato hidráulico / Mecánico', 'tipo_campo' => 'booleano', 'orden' => 1],
            ['codigo' => 'cruceta', 'nombre' => 'Cruceta de llantas', 'tipo_campo' => 'booleano', 'orden' => 2],
            ['codigo' => 'extintor', 'nombre' => 'Extintor vigente', 'tipo_campo' => 'booleano', 'orden' => 3],
            ['codigo' => 'botiquin', 'nombre' => 'Botiquín de primeros auxilios', 'tipo_campo' => 'booleano', 'orden' => 4],
            ['codigo' => 'senalizacion', 'nombre' => 'Señales de carretera (Conos/Triángulos)', 'tipo_campo' => 'booleano', 'orden' => 5],
            ['codigo' => 'radio', 'nombre' => 'Radio / Pantalla multimedia', 'tipo_campo' => 'booleano', 'orden' => 6],
            ['codigo' => 'tapetes', 'nombre' => 'Juego de tapetes', 'tipo_campo' => 'booleano', 'orden' => 7],
        ];

        foreach ($accesoriosCarro as $acc) {
            DB::table('catalogo_accesorios')->updateOrInsert(
                ['tipo_vehiculo_id' => $tipoCarroId, 'codigo' => $acc['codigo']],
                array_merge($acc, ['activo' => true])
            );
        }


        // 3. Catálogo de Piezas de Carrocería (Daños externos - Carro)
        $piezasCarro = [
            ['codigo' => 'parachoque_del', 'nombre' => 'Parachoque / Súper Delantero', 'orden' => 1],
            ['codigo' => 'capot', 'nombre' => 'Capot', 'orden' => 2],
            ['codigo' => 'guardafango_izq', 'nombre' => 'Guardafango Delantero Izquierdo', 'orden' => 3],
            ['codigo' => 'guardafango_der', 'nombre' => 'Guardafango Delantero Derecho', 'orden' => 4],
            ['codigo' => 'puerta_conduc', 'nombre' => 'Puerta Conductor', 'orden' => 5],
            ['codigo' => 'puerta_copiloto', 'nombre' => 'Puerta Copiloto', 'orden' => 6],
            ['codigo' => 'puerta_tras_izq', 'nombre' => 'Puerta Trasera Izquierda', 'orden' => 7],
            ['codigo' => 'puerta_tras_der', 'nombre' => 'Puerta Trasera Derecha', 'orden' => 8],
            ['codigo' => 'techo', 'nombre' => 'Techo', 'orden' => 9],
            ['codigo' => 'compuerta_tras', 'nombre' => 'Compuerta / Baúl / Platón', 'orden' => 10],
            ['codigo' => 'parachoque_tras', 'nombre' => 'Parachoque Trasero', 'orden' => 11],
        ];

        foreach ($piezasCarro as $pieza) {
            DB::table('catalogo_piezas_carroceria')->updateOrInsert(
                ['tipo_vehiculo_id' => $tipoCarroId, 'codigo' => $pieza['codigo']],
                array_merge($pieza, ['activo' => true])
            );
        }


        // 4. Catálogo de Zonas de Cabina (Daños internos)
        $zonasCabina = [
            ['codigo' => 'tapiceria_asientos', 'nombre' => 'Tapicería y Asientos', 'orden' => 1],
            ['codigo' => 'tablero_instrumentos', 'nombre' => 'Tablero de Instrumentos / Mandos', 'orden' => 2],
            ['codigo' => 'volante', 'nombre' => 'Volante y Bocina', 'orden' => 3],
            ['codigo' => 'cinturones', 'nombre' => 'Cinturones de Seguridad', 'orden' => 4],
            ['codigo' => 'cielo_raso', 'nombre' => 'Cielo Raso / Tapizado de techo', 'orden' => 5],
            ['codigo' => 'paneles_puertas', 'nombre' => 'Paneles de Puertas', 'orden' => 6],
        ];

        foreach ($zonasCabina as $zona) {
            DB::table('catalogo_zonas_cabina')->updateOrInsert(
                ['tipo_vehiculo_id' => $tipoCarroId, 'codigo' => $zona['codigo']],
                array_merge($zona, ['activo' => true])
            );
        }


        // 5. Elementos Técnicos Globales (Estructura, Chasis, etc.)
        $elementosTecnicos = [
            ['codigo' => 'chasis_serial', 'nombre' => 'Originalidad de Serial de Chasis', 'orden' => 1],
            ['codigo' => 'motor_serial', 'nombre' => 'Originalidad de Serial de Motor', 'orden' => 2],
            ['codigo' => 'empalmes_costados', 'nombre' => 'Empalmes y Estructura de Costados', 'orden' => 3],
            ['codigo' => 'parales', 'nombre' => 'Parales Delanteros y Traseros', 'orden' => 4],
            ['codigo' => 'piso_baul', 'nombre' => 'Piso de Baúl y BOM', 'orden' => 5],
        ];

        foreach ($elementosTecnicos as $elem) {
            DB::table('catalogo_elementos_tecnicos')->updateOrInsert(
                ['codigo' => $elem['codigo']],
                array_merge($elem, ['activo' => true])
            );
        }


        // 6. Sistemas Mecánicos
        $sistemasMecanicos = [
            ['codigo' => 'motor', 'nombre' => 'Motor y Rendimiento', 'orden' => 1],
            ['codigo' => 'transmision', 'nombre' => 'Transmisión / Caja de Cambios', 'orden' => 2],
            ['codigo' => 'suspension', 'nombre' => 'Suspensión y Amortiguadores', 'orden' => 3],
            ['codigo' => 'frenos', 'nombre' => 'Sistema de Frenos', 'orden' => 4],
            ['codigo' => 'direccion', 'nombre' => 'Sistema de Dirección', 'orden' => 5],
            ['codigo' => 'sistema_electrico', 'nombre' => 'Sistema Eléctrico y Luces', 'orden' => 6],
        ];

        foreach ($sistemasMecanicos as $sis) {
            DB::table('catalogo_sistemas_mecanicos')->updateOrInsert(
                ['tipo_vehiculo_id' => $tipoCarroId, 'codigo' => $sis['codigo']],
                array_merge($sis, ['activo' => true])
            );
        }
    }
}
