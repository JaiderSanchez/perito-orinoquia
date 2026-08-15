<?php

namespace App\Services;

use App\Models\CatalogoAccesorio;
use Illuminate\Support\Str;
use RuntimeException;

class PeritajeAccesorioService
{
    public function guardar($peritaje, $accesorios): void
    {
        $accesorios = $this->normalizar($accesorios);

        if ($accesorios === null) {
            return;
        }

        $tipoVehiculoId = $this->obtenerTipoVehiculoId($peritaje);

        if (!$tipoVehiculoId) {
            throw new RuntimeException('El peritaje no tiene tipo_vehiculo_id.');
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
                'presente' => $this->booleano($item['presente'] ?? false),
                'seleccion' => $item['seleccion'] ?? null,
                'danado' => $this->booleano($item['danado'] ?? false),
                'costo_reparacion' => (int) ($item['costoReparacion'] ?? $item['costo_reparacion'] ?? 0),
                'comentario_dano' => $item['comentarioDaño'] ?? $item['comentario_dano'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($registros) {
            $peritaje->accesorios()->createMany($registros);
        }
    }

    private function normalizar($datos): ?array
    {
        if ($datos === null || $datos === '') {
            return null;
        }

        if (is_string($datos)) {
            $datos = json_decode($datos, true);
        }

        return is_array($datos) ? array_values($datos) : null;
    }

    private function obtenerTipoVehiculoId($peritaje)
    {
        return $peritaje->tipo_vehiculo_id ?? $peritaje->tipoVehiculoId ?? null;
    }

    private function resolverCatalogo(array $item, $tipoVehiculoId)
    {
        $catalogoId = $item['catalogo_accesorio_id'] ?? null;
        $frontendId = $item['id'] ?? null;
        $codigo = $item['codigo'] ?? null;
        $nombre = $item['name'] ?? $item['nombre'] ?? ($item['catalogo_accesorio']['nombre'] ?? null);
        $slug = $item['catalogo_accesorio']['slug'] ?? null;

        if ($catalogoId && $this->esUuid($catalogoId)) {
            $catalogo = CatalogoAccesorio::where('id', $catalogoId)
                ->where('tipo_vehiculo_id', $tipoVehiculoId)
                ->first();

            if ($catalogo) {
                return $catalogo;
            }
        }

        if ($frontendId && $this->esUuid($frontendId)) {
            $catalogo = CatalogoAccesorio::where('id', $frontendId)
                ->where('tipo_vehiculo_id', $tipoVehiculoId)
                ->first();

            if ($catalogo) {
                return $catalogo;
            }
        }

        $catalogo = CatalogoAccesorio::where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where(function ($query) use ($frontendId, $codigo, $slug, $nombre) {
                if ($frontendId && !$this->esUuid($frontendId)) {
                    $query->orWhere('codigo', $frontendId)
                        ->orWhere('slug', $frontendId);
                }

                if ($codigo) {
                    $query->orWhere('codigo', $codigo);
                }

                if ($slug) {
                    $query->orWhere('slug', $slug);
                }

                if ($nombre) {
                    $query->orWhereRaw('LOWER(nombre) = ?', [mb_strtolower(trim($nombre))]);
                }
            })
            ->first();

        if ($catalogo) {
            return $catalogo;
        }

        if (!$nombre) {
            return null;
        }

        $codigoCatalogo = $codigo
            ?? (($frontendId && !$this->esUuid($frontendId)) ? $frontendId : Str::slug($nombre));

        $slugCatalogo = $slug
            ?? Str::slug($codigoCatalogo);

        return CatalogoAccesorio::create([
            'id' => (string) Str::uuid(),
            'tipo_vehiculo_id' => $tipoVehiculoId,
            'nombre' => $nombre,
            'codigo' => $codigoCatalogo,
            'slug' => $slugCatalogo,
            'activo' => true,
        ]);
    }

    private function esUuid($valor): bool
    {
        return is_string($valor) && (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $valor
        );
    }

    private function booleano($valor): int
    {
        return in_array($valor, [true, 1, '1', 'true', 'TRUE', 'si', 'sí'], true) ? 1 : 0;
    }
}
