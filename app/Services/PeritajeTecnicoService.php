<?php

namespace App\Services;

class PeritajeTecnicoService
{
    /**
     * Guarda los detalles técnicos.
     */
    public function guardarDetalles($peritaje, $detalles): void
    {
        $detalles = $this->normalizar($detalles);

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
                'estado' => $item['estado']
                    ?? null,
                'observaciones' => $item['observaciones']
                    ?? null,
            ];
        }

        if (!empty($registros)) {
            $peritaje->detallesTecnicos()->createMany($registros);
        }
    }

    /**
     * Guarda los sistemas mecánicos.
     */
    public function guardarSistemas($peritaje, $sistemas): void
    {
        $sistemas = $this->normalizar($sistemas);

        if ($sistemas === null) {
            return;
        }

        $peritaje->sistemasMecanicos()->delete();

        $registros = [];

        foreach ($sistemas as $item) {
            if (!is_array($item)) {
                continue;
            }

            $catalogoId = $item['catalogo_sistema_id']
                ?? $item['id']
                ?? null;

            /*
             * Los sistemas mecánicos de tu controller
             * solamente aceptaban UUID.
             */
            if (!$this->esUuid($catalogoId)) {
                continue;
            }

            $registros[] = [
                'catalogo_sistema_id' => $catalogoId,
                'estado' => $item['estado']
                    ?? null,
                'observaciones' => $item['observaciones']
                    ?? null,
            ];
        }

        if (!empty($registros)) {
            $peritaje->sistemasMecanicos()->createMany($registros);
        }
    }

    /**
     * Normaliza datos provenientes de JSON/FormData.
     */
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

    /**
     * Valida UUID o ID numérico.
     */
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

    /**
     * Valida UUID.
     */
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
