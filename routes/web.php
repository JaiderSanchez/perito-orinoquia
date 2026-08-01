<?php

use Illuminate\Support\Facades\Route;
use App\Models\User; // <-- 1. Agregamos el modelo User aquí arriba

Route::get('/', function () {
    return view('welcome');
});

// <-- 2. Agregamos la ruta de prueba aquí abajo
Route::get('/test-db', function () {
    return User::all();
});
