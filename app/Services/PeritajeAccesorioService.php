<?php

namespace App\Services;

use App\Models\CatalogoAccesorio;
use Illuminate\Support\Str;

class PeritajeAccesorioService
{
    /**
     * Guarda los accesorios asociados a un peritaje.
     */
    public function guardar($peritaje, $accesorios, $tipoVehiculoId = null): void
    {
        if (is_string($accesorios)) {
            $accesorios = json_decode($accesorios, true);
        }

        if (!is_array($accesorios)) {
            return;
        }

        $peritaje->accesorios()->delete();

        $registros = [];

        foreach ($accesorios as $item) {
            if (!is_array($item)) {
                continue;
            }

            $catalogo = $this->resolverCatalogo($item, $tipoVehiculoId);

            if (!$catalogo) {
                continue;
            }

            $registros[] = [
                'id' => (string) Str::uuid(),
                'peritaje_id' => $peritaje->id,
                'catalogo_accesorio_id' => $catalogo->id,

                'presente' => $this->booleano(
                    $item['presente'] ?? true
                ),

                'seleccion' => $item['seleccion'] ?? null,

                'danado' => $this->booleano(
                    $item['danado'] ?? false
                ),

                'costo_reparacion' => (int) (
                    $item['costoReparacion']
                    ?? $item['costo_reparacion']
                    ?? 0
                ),

                'comentario_dano' => $item['comentarioDaño']
                    ?? $item['comentario_dano']
                    ?? null,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($registros)) {
            $peritaje->accesorios()->createMany($registros);
        }
    }

    /**
     * Busca o crea el accesorio del catálogo.
     */
    private function resolverCatalogo(array $item, $tipoVehiculoId = null)
    {
        $frontendId = $item['id']
            ?? $item['catalogo_accesorio_id']
            ?? null;

        if (!$frontendId) {
            return null;
        }

        $catalogo = null;

        /*
         * Primero intentamos buscar por UUID.
         */
        if ($this->esUuid($frontendId)) {
            $catalogo = CatalogoAccesorio::find($frontendId);
        }

        /*
         * Si no existe, buscamos por código o slug.
         */
        if (!$catalogo) {
            $catalogo = CatalogoAccesorio::where('codigo', $frontendId)
                ->orWhere('slug', $frontendId)
                ->first();
        }

        /*
         * Si el frontend envía un accesorio que todavía
         * no existe en el catálogo, lo creamos.
         */
        if (!$catalogo && !empty($item['name'])) {
            $catalogo = CatalogoAccesorio::create([
                'id' => (string) Str::uuid(),
                'nombre' => $item['name'],
                'codigo' => $frontendId,
                'slug' => $frontendId,
                'tipo_vehiculo_id' => $tipoVehiculoId,
                'activo' => true,
            ]);
        }

        return $catalogo;
    }

    /**
     * Determina si un valor es UUID.
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

    /**
     * Convierte valores del frontend a 0/1.
     */
    private function booleano($valor): int
    {
        return filter_var(
            $valor,
            FILTER_VALIDATE_BOOLEAN
        ) ? 1 : 0;
    }
}
