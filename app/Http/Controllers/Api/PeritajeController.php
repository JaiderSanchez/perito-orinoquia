<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class PeritajeController extends Controller
{
    public function __construct(
        private PeritajeService $peritajeService
    ) {
    }

    public function index()
    {
        return $this->peritajeService->listar();
    }
    public function show($id)
    {
        return $this->peritajeService->mostrar($id);
    }

    public function store(Request $request)
    {
        return $this->peritajeService->crear($request);
    }

    public function update(Request $request, $id)
    {
        return $this->peritajeService->actualizar($request, $id);
    }

    public function destroy($id)
    {
        return $this->peritajeService->eliminar($id);
    }
}
