<?php

namespace App\Services;

class PeritajeTecnicoService
{
    public function guardarDetalles($peritaje, $datos): void
    {
        $detalles = $this->obtenerDatos(
            $datos,
            [
                'detalles_tecnicos',
                'detallesTecnicos',
                'peritaje_detalles_tecnicos',
            ]
        );

        if ($detalles === null) {
            return;
        }

        $peritaje->detallesTecnicos()->delete();

        $registros = [];

        foreach ($detalles as $item) {
            if (!is_array($item)) {
                continue;
            }

            $catalogoId = $item['catalogo_elemento_id']
                ?? $item['id']
                ?? null;

            if (!$this->idValido($catalogoId)) {
                continue;
            }

            $registros[] = [
                'catalogo_elemento_id' => $catalogoId,
                'estado' => $item['estado'] ?? null,
                'observaciones' => $item['observaciones'] ?? null,
            ];
        }

        if (!empty($registros)) {
            $peritaje->detallesTecnicos()->createMany($registros);
        }
    }

    public function guardarSistemas($peritaje, $datos): void
    {
        $sistemas = $this->obtenerDatos(
            $datos,
            [
                'sistemas_mecanicos',
                'sistemasMecanicos',
                'peritaje_sistemas_mecanicos',
            ]
        );

        // Elimina los registros anteriores del peritaje para limpiar y actualizar
        $peritaje->sistemasMecanicos()->delete();

        if ($sistemas === null || !is_array($sistemas)) {
            return;
        }

        $registros = [];

        foreach ($sistemas as $key => $item) {
            // Si el item viene como arreglo con 'estado' u 'observaciones'
            if (is_array($item)) {
                $estado = $item['estado'] ?? null;
                $observaciones = $item['observaciones'] ?? null;
            } else {
                $estado = $item;
                $observaciones = null;
            }

            if ($estado === null && $observaciones === null) {
                continue;
            }

            $registros[] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'peritaje_id' => $peritaje->id,
                'item_key' => $key, // Ejemplo: 'fugasMotor', 'correas', etc.
                'estado' => $estado,
                'observaciones' => $observaciones,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($registros)) {
            \App\Models\PeritajeSistemaMecanico::insert($registros);
        }
    }

    private function obtenerDatos($datos, array $campos): ?array
    {
        if ($datos === null) {
            return null;
        }

        if (is_array($datos)) {
            return $datos;
        }

        foreach ($campos as $campo) {
            if (method_exists($datos, 'has')) {
                if (!$datos->has($campo)) {
                    continue;
                }

                return $this->normalizar(
                    $datos->input($campo)
                );
            }
        }

        return null;
    }

    private function normalizar($datos): ?array
    {
        if ($datos === null || $datos === '') {
            return null;
        }

        if (is_string($datos)) {
            $datos = json_decode($datos, true);
        }

        return is_array($datos) ? $datos : null;
    }

    private function idValido($id): bool
    {
        if ($id === null || $id === '') {
            return false;
        }

        if (is_numeric($id)) {
            return true;
        }

        return $this->esUuid($id);
    }

    private function esUuid($valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }

        return (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $valor
        );
    }
}
