<?php

use App\Models\Peritaje;
use App\Models\User;
use App\Services\PeritajeArchivoService;
use Illuminate\Http\Request;

it('solo permite al inspector propietario gestionar archivos', function () {
    $usuario = new User(['rol' => 'inspector']);
    $usuario->id = '11111111-1111-4111-8111-111111111111';

    $request = Request::create('/');
    $request->setUserResolver(fn () => $usuario);

    $peritajeAjeno = new Peritaje();
    $peritajeAjeno->inspector_id = '22222222-2222-4222-8222-222222222222';

    $peritajePropio = new Peritaje();
    $peritajePropio->inspector_id = $usuario->id;

    $servicio = new PeritajeArchivoService();

    expect($servicio->puedeGestionarPeritaje($request, $peritajeAjeno))->toBeFalse()
        ->and($servicio->puedeGestionarPeritaje($request, $peritajePropio))->toBeTrue();
});

it('permite a administradores y superadministradores gestionar archivos', function (string $rol) {
    $usuario = new User(['rol' => $rol]);
    $usuario->id = '11111111-1111-4111-8111-111111111111';

    $request = Request::create('/');
    $request->setUserResolver(fn () => $usuario);

    $peritaje = new Peritaje();
    $peritaje->inspector_id = '22222222-2222-4222-8222-222222222222';

    expect((new PeritajeArchivoService())->puedeGestionarPeritaje($request, $peritaje))->toBeTrue();
})->with(['admin', 'superadmin']);
