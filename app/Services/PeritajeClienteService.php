<?php

namespace App\Services;

use App\Models\PeritajeCliente;
use Illuminate\Support\Str;

class PeritajeClienteService
{
    public function guardar(
        $peritaje,
        ?string $nombre,
        ?string $documento,
        ?string $telefono
    ): ?PeritajeCliente {
        // Si no hay documento ni nombre, no intentamos guardar nada innecesario
        if (empty($documento) && empty($nombre)) {
            return null;
        }

        // Si el documento viene vacío pero hay nombre, le asignamos uno temporal o evitamos el fallo
        $documentoFinal = $documento ?? 'SIN-DOCUMENTO-' . $peritaje->id;

        return PeritajeCliente::updateOrCreate(
            [
                'peritaje_id' => $peritaje->id,
            ],
            [
                'documento_cliente' => $documentoFinal,
                'nombre_cliente' => $nombre,
                'telefono_cliente' => $telefono, // <-- Corregido: ahora coincide con la columna "telefono_cliente" de la base de datos
            ]
        );
    }

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
