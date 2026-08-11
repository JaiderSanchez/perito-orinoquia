<?php

use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\PeritajeArchivoController;
use App\Http\Controllers\Api\PeritajeController;
use App\Http\Controllers\Api\PeritajeItemController;
use App\Http\Controllers\Api\PeritajePdfController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// ==========================================
// 1. RUTAS PÚBLICAS (Login, Catálogos y Creación)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);

// Catálogos públicos y búsqueda:
Route::get('tipos-vehiculo', [CatalogoController::class, 'tiposVehiculo']);
Route::get('tipos-vehiculo/{tipoVehiculo}/checklist', [CatalogoController::class, 'checklist']);
Route::get('sucursales', [CatalogoController::class, 'sucursales']);
Route::get('vendedores', [CatalogoController::class, 'vendedores']);
Route::get('/clientes/buscar', [PeritajeController::class, 'buscarClientes']);

// Ruta de creación de peritajes temporalmente pública para evitar bloqueos de token:
Route::post('peritajes', [PeritajeController::class, 'store']);

// ==========================================
// 2. RUTAS PROTEGIDAS (Requieren Token Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // Perfil y Contraseña del usuario autenticado
    Route::put('user/password', [AuthController::class, 'updatePassword']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);

    // Gestión de Usuarios (CRUD completo)
    Route::apiResource('users', AuthController::class)->middleware('admin');

    // Sucursales y Vendedores (Escritura)
    Route::post('sucursales', [CatalogoController::class, 'storeSucursal']);
    Route::post('vendedores', [CatalogoController::class, 'storeVendedor']);

    // Peritajes (Lectura y acciones protegidas)
    Route::get('peritajes', [PeritajeController::class, 'index']);
    Route::get('peritajes/{peritaje}', [PeritajeController::class, 'show']);

    // Rutas de actualización completa o parcial del peritaje
    Route::put('peritajes/{peritaje}', [PeritajeController::class, 'update']);
    Route::patch('peritajes/{peritaje}', [PeritajeController::class, 'update']);

    Route::patch('peritajes/{peritaje}/estado', [PeritajeController::class, 'cambiarEstado']);
    Route::delete('peritajes/{peritaje}', [PeritajeController::class, 'destroy']);

    // Checklists por ítem
    Route::put('peritajes/{peritaje}/accesorios/{catalogoAccesorioId}', [PeritajeItemController::class, 'upsertAccesorio']);
    Route::put('peritajes/{peritaje}/danos-externos/{catalogoPiezaId}', [PeritajeItemController::class, 'upsertDanoExterno']);
    Route::put('peritajes/{peritaje}/danos-internos/{catalogoZonaId}', [PeritajeItemController::class, 'upsertDanoInterno']);
    Route::put('peritajes/{peritaje}/detalles-tecnicos/{catalogoElementoId}', [PeritajeItemController::class, 'upsertDetalleTecnico']);
    Route::put('peritajes/{peritaje}/sistemas-mecanicos/{catalogoSistemaId}', [PeritajeItemController::class, 'upsertSistemaMecanico']);
    Route::put('peritajes/{peritaje}/compresion', [PeritajeItemController::class, 'upsertCompresion']);

    // Archivos y Firmas
    Route::post('peritajes/{peritaje}/archivos', [PeritajeArchivoController::class, 'store']);
    Route::post('peritajes/{peritaje}/firma', [PeritajeArchivoController::class, 'guardarFirma']);
    Route::delete('archivos/{archivo}', [PeritajeArchivoController::class, 'destroy']);

    // PDF
    Route::get('peritajes/{peritaje}/pdf', [PeritajePdfController::class, 'generar']);
});
