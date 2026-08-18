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

     public function guardarSistemas(\App\Models\Peritaje $peritaje, $sistemas): void
    {
        if (is_string($sistemas)) {
            $sistemas = json_decode($sistemas, true) ?? [];
        }

        if (!is_array($sistemas)) {
            return;
        }

        // Contrato esperado: objeto {sistema_key: {estado, observaciones}}
        // ej: {"fugasMotor": {"estado":"BUENO","observaciones":"..."}, ...}
        foreach ($sistemas as $sistemaKey => $valor) {
            if (!is_string($sistemaKey) || !is_array($valor)) {
                continue;
            }

            $estado = $valor['estado'] ?? null;
            $observaciones = $valor['observaciones'] ?? null;

            // El check constraint chk_estado_mecanico probablemente exige
            // BUENO/REGULAR/MALO, así que solo guardamos si hay estado.
            if (empty($estado)) {
                continue;
            }

            \App\Models\PeritajeSistemaMecanico::updateOrCreate(
                [
                    'peritaje_id' => $peritaje->id,
                    'sistema_key' => $sistemaKey,
                ],
                [
                    'estado' => $estado,
                    'observaciones' => $observaciones,
                ]
            );
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
