<?php

namespace App\Services;

use App\Models\Archivo;
use App\Models\Peritaje;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PeritajeArchivoService
{
    private const CATEGORIAS_PERMITIDAS = [
        'SOAT',
        'RTM',
        'FIRMA_INSPECTOR',
        'FOTO_DANO_EXTERNO',
        'FOTO_DETALLE_TECNICO',
        'FOTO_ACCESORIO',
        'OTRO',
    ];

    public function puedeGestionarPeritaje(
        Request $request,
        Peritaje $peritaje
    ): bool {
        $usuario = $request->user();

        if (!$usuario) {
            return false;
        }

        if (
            isset($usuario->rol)
            && in_array(
                strtoupper((string) $usuario->rol),
                ['ADMIN', 'ADMINISTRADOR'],
                true
            )
        ) {
            return true;
        }

        if (
            isset($peritaje->inspector_id)
            && (int) $peritaje->inspector_id === (int) $usuario->id
        ) {
            return true;
        }

        return false;
    }
    
    public function guardar(
        Request $request,
        Peritaje $peritaje
    ): Archivo {
        $request->validate([
            'archivo' => [
                'required',
                'file',
                'max:10240',
            ],
            'categoria' => [
                'required',
                'string',
                'in:' . implode(',', self::CATEGORIAS_PERMITIDAS),
            ],
            'entidad_relacionada_id' => [
                'nullable',
                'uuid',
            ],
        ]);

        /** @var UploadedFile $archivo */
        $archivo = $request->file('archivo');

        $categoria = strtoupper(
            (string) $request->input('categoria')
        );

        return $this->guardarArchivo(
            $archivo,
            $peritaje,
            $categoria,
            $request->input('entidad_relacionada_id')
        );
    }

    public function guardarFirma(
        Request $request,
        Peritaje $peritaje
    ): Archivo {
        $request->validate([
            'firma' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg',
                'max:5120',
            ],
        ]);

        /** @var UploadedFile $firma */
        $firma = $request->file('firma');

        /*
         * Si ya existe una firma anterior, eliminamos
         * el archivo físico y el registro.
         */
        $firmasAnteriores = $peritaje->archivos()
            ->where('categoria', 'FIRMA_INSPECTOR')
            ->get();

        foreach ($firmasAnteriores as $firmaAnterior) {
            $this->eliminar($firmaAnterior);
        }

        return $this->guardarArchivo(
            $firma,
            $peritaje,
            'FIRMA_INSPECTOR'
        );
    }

    private function guardarArchivo(
        UploadedFile $archivo,
        Peritaje $peritaje,
        string $categoria,
        ?string $entidadRelacionadaId = null
    ): Archivo {
        if (
            !in_array(
                $categoria,
                self::CATEGORIAS_PERMITIDAS,
                true
            )
        ) {
            abort(
                422,
                'La categoría del archivo no es válida.'
            );
        }

        $extension = strtolower(
            $archivo->getClientOriginalExtension()
        );

        $nombreOriginal = $archivo->getClientOriginalName();

        $nombreArchivo = Str::uuid()
            . ($extension ? '.' . $extension : '');

        $directorio = 'peritajes/' . $peritaje->id;

        $ruta = $archivo->storeAs(
            $directorio,
            $nombreArchivo,
            'public'
        );

        try {
            return Archivo::create([
                'peritaje_id' => $peritaje->id,
                'categoria' => $categoria,
                'entidad_relacionada_id' => $entidadRelacionadaId,
                'nombre_original' => $nombreOriginal,
                'mime_type' => $archivo->getMimeType()
                    ?: $archivo->getClientMimeType(),
                'url' => $ruta,
                'tamanio_bytes' => $archivo->getSize(),
                'subido_por' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            /*
             * Si falla PostgreSQL después de guardar el archivo,
             * eliminamos el archivo físico para no dejar basura.
             */
            Storage::disk('public')->delete($ruta);

            throw $e;
        }
    }

    public function eliminar(Archivo $archivo): void
    {
        if ($archivo->url) {
            Storage::disk('public')->delete(
                $archivo->url
            );
        }

        $archivo->delete();
    }
}
