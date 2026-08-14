<?php

namespace App\Services;

class PeritajeDanosService
{
    /**
     * Guarda los daños externos del peritaje.
     */
    public function guardarExternos($peritaje, $danos): void
    {
        $danos = $this->normalizar($danos);

        if ($danos === null) {
            return;
        }

        $peritaje->danosExternos()->delete();

        $registros = [];

        foreach ($danos as $item) {
            if (!is_array($item)) {
                continue;
            }

            $catalogoId = $item['catalogo_pieza_id']
                ?? $item['id']
                ?? null;

            if (!$this->idValido($catalogoId)) {
                continue;
            }

            $registros[] = [
                'catalogo_pieza_id' => $catalogoId,
                'tipo_dano' => $item['tipo_dano']
                    ?? $item['tipoDano']
                    ?? null,
                'observaciones' => $item['observaciones']
                    ?? null,
            ];
        }

        if (!empty($registros)) {
            $peritaje->danosExternos()->createMany($registros);
        }
    }

    /**
     * Guarda los daños internos del peritaje.
     */
    public function guardarInternos($peritaje, $danos): void
    {
        $danos = $this->normalizar($danos);

        if ($danos === null) {
            return;
        }

        $peritaje->danosInternos()->delete();

        $registros = [];

        foreach ($danos as $item) {
            if (!is_array($item)) {
                continue;
            }

            $catalogoId = $item['catalogo_zona_id']
                ?? $item['id']
                ?? null;

            if (!$this->idValido($catalogoId)) {
                continue;
            }

            $registros[] = [
                'catalogo_zona_id' => $catalogoId,
                'estado' => $item['estado'] ?? null,
                'observaciones' => $item['observaciones'] ?? null,
            ];
        }

        if (!empty($registros)) {
            $peritaje->danosInternos()->createMany($registros);
        }
    }

    /**
     * Actualiza los daños externos.
     */
    public function actualizarExternos($peritaje, $danos): void
    {
        $this->guardarExternos($peritaje, $danos);
    }

    /**
     * Actualiza los daños internos.
     */
    public function actualizarInternos($peritaje, $danos): void
    {
        $this->guardarInternos($peritaje, $danos);
    }

    /**
     * Convierte JSON a array cuando sea necesario.
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
     * Valida UUID o identificadores numéricos.
     */
    private function idValido($id): bool
    {
        if ($id === null || $id === '') {
            return false;
        }

        if (is_numeric($id)) {
            return true;
        }

        if (!is_string($id)) {
            return false;
        }

        return (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $id
        );
    }
}
