<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PeritajeService;
use Illuminate\Http\Request;

class PeritajeController extends Controller
{
    public function __construct(
        private PeritajeService $peritajeService
    ) {
    }

    public function index()
    {
        return $this->peritajeService->index();
    }

    public function show($id)
    {
        return $this->peritajeService->show($id);
    }

    public function store(Request $request)
    {
        return $this->peritajeService->store($request);
    }

    public function update(Request $request, $id)
    {
        return $this->peritajeService->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->peritajeService->destroy($id);
    }

    public function buscarClientes(Request $request)
    {
        return $this->peritajeService->buscarClientes(
            $request->input('query')
        );
    }
}
