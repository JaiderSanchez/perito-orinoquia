<?php

use App\Models\Peritaje;
use App\Services\PeritajeImagenService;
use App\Services\PeritajeService;
use App\Services\PeritajeTecnicoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

// Esquema aislado SQLite: prueba la persistencia de estos servicios sin tocar
// la base local. Las migraciones históricas usan sentencias exclusivas de PostgreSQL.
beforeEach(function () {
    Schema::create('peritajes', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('codigo')->unique();
        foreach (['placa', 'marca', 'linea', 'num_motor', 'num_chasis', 'organismo_transito', 'tipo_vehiculo_id', 'numero_control_rtm'] as $campo) {
            $table->string($campo)->nullable();
        }
        $table->integer('modelo_anio')->nullable();
        $table->timestamps();
    });
    (require database_path('migrations/2026_10_23_212958_add_evaluaciones_visuales_to_peritajes_table.php'))->up();
    Schema::create('peritaje_detalles_tecnicos', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('peritaje_id');
        $table->uuid('catalogo_elemento_id')->nullable();
        $table->string('elemento_key')->nullable();
        $table->boolean('danado');
        $table->string('comentario')->nullable();
        $table->decimal('costo', 14, 2)->nullable();
        $table->timestamps();
    });
    Schema::create('peritaje_imagenes', function (Blueprint $table) {
        $table->id();
        $table->uuid('peritaje_id');
        $table->string('seccion');
        $table->string('item_id')->nullable();
        $table->text('imagen_base64');
        $table->string('nombre_archivo')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('peritaje_imagenes');
    Schema::dropIfExists('peritaje_detalles_tecnicos');
    Schema::dropIfExists('peritajes');
});

function datosPrincipalesParaPrueba(array $datos, ?Peritaje $peritaje = null): array
{
    $servicio = app(PeritajeService::class);

    return (new ReflectionMethod($servicio, 'prepararDatosPrincipales'))
        ->invoke($servicio, Request::create('/', 'POST', $datos), $peritaje);
}

it('persiste evaluaciones sin catálogo y mantiene aislados dos peritajes', function () {
    $a = Peritaje::create(datosPrincipalesParaPrueba([
        'numero_control_rtm' => 'RTM-123',
        'danos_externos' => json_encode([['id' => 'capo', 'tipo' => 'Rayón', 'micras' => 120, 'comentario' => 'Reparar']]),
        'danos_internos' => json_encode([['id' => 'tablero', 'estado' => 'Regular', 'desgaste' => 'Acelerado']]),
    ]));
    $b = Peritaje::create(datosPrincipalesParaPrueba(['danos_externos' => '[]']));
    expect($a->fresh()->evaluaciones_visuales['danos_externos'][0]['comentario'])->toBe('Reparar')
        ->and($a->fresh()->evaluaciones_visuales['danos_internos'][0]['desgaste'])->toBe('Acelerado')
        ->and($a->fresh()->numero_control_rtm)->toBe('RTM-123')
        ->and($b->fresh()->evaluaciones_visuales['danos_externos'])->toBe([]);
});

it('editar una sección conserva la otra y una lista vacía borra la evaluación', function () {
    $p = Peritaje::create(datosPrincipalesParaPrueba(['danos_externos' => '[{"id":"capo"}]', 'danos_internos' => '[{"id":"tablero"}]']));
    $p->update(datosPrincipalesParaPrueba(['danos_externos' => '[]'], $p));
    expect($p->fresh()->evaluaciones_visuales['danos_externos'])->toBe([])
        ->and($p->fresh()->evaluaciones_visuales['danos_internos'][0]['id'])->toBe('tablero');
});

it('rechaza JSON visual inválido en lugar de confirmar un guardado incompleto', function () {
    datosPrincipalesParaPrueba(['danos_externos' => '{invalido']);
})->throws(ValidationException::class);

it('guarda detalles técnicos multipart JSON y permite editarlos sin duplicar ni mezclar peritajes', function () {
    $a = Peritaje::create(datosPrincipalesParaPrueba([]));
    $b = Peritaje::create(datosPrincipalesParaPrueba([]));
    $servicio = app(PeritajeTecnicoService::class);
    $servicio->guardarDetalles($a, '[{"id":"motor","danado":true,"comentario":"Fuga","costo":50000}]');
    $servicio->guardarDetalles($b, '[{"id":"motor","danado":false,"comentario":"Correcto","costo":0}]');
    expect($a->detallesTecnicos()->first()->comentario)->toBe('Fuga');
    $servicio->guardarDetalles($a, '[{"id":"motor","danado":true,"comentario":"Revisado","costo":25000}]');
    expect($a->detallesTecnicos()->count())->toBe(1)
        ->and($a->detallesTecnicos()->first()->comentario)->toBe('Revisado')
        ->and($b->detallesTecnicos()->first()->comentario)->toBe('Correcto');
    $servicio->guardarDetalles($a, '[]');
    expect($a->detallesTecnicos()->count())->toBe(0);
});

it('una actualización sin imágenes conserva las evidencias y una lista explícita permite borrarlas', function () {
    $a = Peritaje::create(datosPrincipalesParaPrueba([]));
    $b = Peritaje::create(datosPrincipalesParaPrueba([]));
    $servicio = app(PeritajeImagenService::class);
    $imagen = [['seccion' => 'vista_externa', 'item_id' => 'capo', 'imagen_base64' => 'data:image/png;base64,AA']];
    foreach ([$a, $b] as $p) {
        $servicio->guardar($p, Request::create('/', 'POST', ['imagenes' => json_encode($imagen)]));
    }
    $servicio->guardar($a, Request::create('/', 'POST', ['placa' => 'ABC123']));
    expect($a->imagenes()->count())->toBe(1);
    $servicio->guardar($a, Request::create('/', 'POST', ['imagenes' => '[]']));
    expect($a->imagenes()->count())->toBe(0)->and($b->imagenes()->count())->toBe(1);
});
