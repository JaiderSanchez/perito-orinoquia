<?php

namespace App\Services;

use App\Models\CatalogoAccesorio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PeritajeAccesorioService
{
    public function guardar($peritaje, Request $request): void
    {
        $accesorios = $request->input('accesorios')
            ?? $request->input('accesoriosList')
            ?? $request->input('peritaje_accesorios');

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

            $catalogo = $this->resolverCatalogo($item);

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

        if ($registros) {
            $peritaje->accesorios()->createMany($registros);
        }
    }

    private function resolverCatalogo(array $item)
    {
        $frontendId = $item['id']
            ?? $item['catalogo_accesorio_id']
            ?? null;

        if (!$frontendId) {
            return null;
        }

        $catalogo = null;

        if ($this->esUuid($frontendId)) {
            $catalogo = CatalogoAccesorio::find($frontendId);
        }

        if (!$catalogo) {
            $catalogo = CatalogoAccesorio::where(
                'codigo',
                $frontendId
            )
                ->orWhere('slug', $frontendId)
                ->first();
        }

        if (!$catalogo && !empty($item['name'])) {
            $catalogo = CatalogoAccesorio::create([
                'id' => (string) Str::uuid(),
                'nombre' => $item['name'],
                'codigo' => $frontendId,
                'slug' => Str::slug($frontendId),
                'activo' => true,
            ]);
        }

        return $catalogo;
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

    private function booleano($valor): int
    {
        return filter_var(
            $valor,
            FILTER_VALIDATE_BOOLEAN
        ) ? 1 : 0;
    }
}
