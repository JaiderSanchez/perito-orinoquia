<?php

namespace App\Services;

use App\Models\Peritaje;
use App\Models\PeritajeCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PeritajeClienteService
{
    /**
     * Guarda o actualiza el cliente asociado al peritaje.
     */
    public function guardar(
        Peritaje $peritaje,
        Request $request
    ): ?PeritajeCliente {
        $nombre = $request->input('clienteNombre')
            ?? $request->input('cliente_nombre')
            ?? $request->input('nombre_cliente');

        $documento = $request->input('clienteDocumento')
            ?? $request->input('cliente_documento')
            ?? $request->input('documento_cliente');

        $telefono = $request->input('clienteTelefono')
            ?? $request->input('cliente_telefono')
            ?? $request->input('telefono_cliene');

        if (!$documento) {
            return null;
        }

        return PeritajeCliente::updateOrCreate(
            [
                'documento_cliente' => $documento,
            ],
            [
                'id' => (string) Str::uuid(),
                'peritaje_id' => $peritaje->id,
                'nombre_cliente' => $nombre,
                'telefono_cliene' => $telefono,
            ]
        );
    }

    /**
     * Busca clientes por nombre o documento.
     */
    public function buscar(?string $query)
    {
        return PeritajeCliente::when(
            $query,
            function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where(
                        'nombre_cliente',
                        'like',
                        "%{$query}%"
                    )->orWhere(
                        'documento_cliente',
                        'like',
                        "%{$query}%"
                    );
                });
            }
        )
            ->limit(10)
            ->get();
    }
}
