<?php

namespace App\Services;

use App\Models\PeritajeCliente;
use Illuminate\Support\Str;

class PeritajeClienteService
{
    /**
     * Guarda o actualiza el cliente asociado al peritaje.
     */
    public function guardar(
        $peritaje,
        ?string $nombre,
        ?string $documento,
        ?string $telefono
    ): ?PeritajeCliente {
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
