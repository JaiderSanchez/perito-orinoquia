<?php

namespace App\Services;

use App\Models\Peritaje;
use App\Models\PeritajeCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\PeritajeAccesorioService;
use App\Services\PeritajeClienteService;
use App\Services\PeritajeCompresionService;
use App\Services\PeritajeDanosService;
use App\Services\PeritajeImagenService;
use App\Services\PeritajeTecnicoService;

class PeritajeService
{
    public function __construct(
        protected PeritajeAccesorioService $accesorioService,
        protected PeritajeClienteService $clienteService,
        protected PeritajeCompresionService $compresionService,
        protected PeritajeDanosService $danosService,
        protected PeritajeImagenService $imagenService,
        protected PeritajeTecnicoService $tecnicoService
    ) {
    }

    /**
     * Relaciones utilizadas en las respuestas del peritaje.
     */
    protected function relaciones(): array
    {
        return [
            'sucursalVendedor',
            'sucursalInspeccion',
            'vendedor',
            'inspector',
            'accesorios',
            'danosExternos',
            'danosInternos',
            'detallesTecnicos',
            'sistemasMecanicos',
            'compresionCilindros',
            'imagenes',
        ];
    }

    /**
     * Relaciones utilizadas para mostrar un peritaje individual.
     */
    protected function relacionesShow(): array
    {
        return [
            'sucursalVendedor',
            'sucursalInspeccion',
            'vendedor',
            'inspector',
            'cliente',
            'accesorios.catalogoAccesorio',
            'danosExternos',
            'danosInternos',
            'detallesTecnicos',
            'sistemasMecanicos',
            'compresionCilindros',
            'imagenes',
        ];
    }

    /**
     * Determina si el usuario autenticado es administrador.
     */
    public function esAdmin(): bool
    {
        return auth()->user()?->rol === 'admin';
    }

    /**
     * Lista los peritajes.
     *
     * Los administradores pueden ver todos.
     * Los inspectores solamente los propios.
     */
    public function index()
    {
        $query = Peritaje::with($this->relaciones());

        if (!$this->esAdmin()) {
            $query->where('inspector_id', auth()->id());
        }

        return $query->latest()->get();
    }

    /**
     * Obtiene un peritaje específico.
     */
    public function show($id): Peritaje
    {
        $peritaje = Peritaje::with($this->relacionesShow())
            ->findOrFail($id);

        $this->verificarPermiso($peritaje);

        $this->formatearAccesorios($peritaje);
        $this->formatearImagenes($peritaje);

        return $peritaje;
    }

    /**
     * Crea un nuevo peritaje.
     */
    public function store(Request $request): Peritaje
    {
        return DB::transaction(function () use ($request) {

            $data = $this->prepararDatosPrincipales($request);

            $data['inspector_id'] = auth()->id();

            $peritaje = Peritaje::create($data);

            $this->clienteService->guardar($peritaje, $request);

            $this->accesorioService->guardar($peritaje, $request);

            $this->danosService->guardarExternos($peritaje, $request);

            $this->danosService->guardarInternos($peritaje, $request);

            $this->tecnicoService->guardarDetalles($peritaje, $request);

            $this->tecnicoService->guardarSistemas($peritaje, $request);

            $this->compresionService->guardar($peritaje, $request);

            $this->imagenService->guardar($peritaje, $request);

            return $peritaje->load($this->relaciones());
        });
    }

    /**
     * Actualiza un peritaje existente.
     */
    public function update(Request $request, $id): Peritaje
    {
        return DB::transaction(function () use ($request, $id) {

            $peritaje = Peritaje::findOrFail($id);

            $this->verificarPermiso($peritaje);

            $data = $this->prepararDatosPrincipales(
                $request,
                $peritaje
            );

            $peritaje->update($data);

            $this->clienteService->guardar($peritaje, $request);

            $this->accesorioService->guardar($peritaje, $request);

            $this->danosService->actualizarExternos(
                $peritaje,
                $request
            );

            $this->danosService->actualizarInternos(
                $peritaje,
                $request
            );

            $this->tecnicoService->actualizarDetalles(
                $peritaje,
                $request
            );

            $this->tecnicoService->actualizarSistemas(
                $peritaje,
                $request
            );

            $this->compresionService->guardar(
                $peritaje,
                $request
            );

            $this->imagenService->guardar(
                $peritaje,
                $request
            );

            return $peritaje->load($this->relaciones());
        });
    }

    /**
     * Elimina un peritaje.
     */
    public function destroy($id): void
    {
        if (!$this->esAdmin()) {
            abort(403, 'Solo los administradores pueden eliminar peritajes.');
        }

        DB::transaction(function () use ($id) {

            $peritaje = Peritaje::findOrFail($id);

            $this->eliminarArchivos($peritaje);

            $peritaje->accesorios()->delete();
            $peritaje->danosExternos()->delete();
            $peritaje->danosInternos()->delete();
            $peritaje->detallesTecnicos()->delete();
            $peritaje->sistemasMecanicos()->delete();
            $peritaje->compresionCilindros()->delete();
            $peritaje->imagenes()->delete();

            if (method_exists($peritaje, 'archivos')) {
                $peritaje->archivos()->delete();
            }

            if (method_exists($peritaje, 'historialEstados')) {
                $peritaje->historialEstados()->delete();
            }

            $peritaje->delete();
        });
    }

    /**
     * Busca clientes.
     */
    public function buscarClientes(?string $query)
    {
        return PeritajeCliente::when($query, function ($builder) use ($query) {

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

        })
            ->limit(10)
            ->get();
    }

    /**
     * Prepara los campos principales del peritaje.
     */
    protected function prepararDatosPrincipales(
        Request $request,
        ?Peritaje $peritaje = null
    ): array {

        $data = $request->except([
            'accesorios',
            'accesoriosList',
            'peritaje_accesorios',

            'danos_externos',
            'danosExternos',
            'danos_internos',
            'danosInternos',

            'detalles_tecnicos',
            'detallesTecnicos',

            'sistemas_mecanicos',
            'sistemasMecanicos',

            'compresion_cilindros',
            'compresionCilindros',

            'imagenes',
            'peritaje_imagenes',

            'archivo_soat',
            'archivoSoat',

            'archivo_tecnico_mecanica',
            'archivoTecnicoMecanica',
        ]);

        $mapeo = [
            'sucursalVendedorId' => 'sucursal_vendedor_id',
            'sucursalInspeccionId' => 'sucursal_inspeccion_id',
            'vendedorId' => 'vendedor_id',

            'tipoVehiculo' => 'tipo_vehiculo',
            'modeloAnio' => 'modelo_anio',

            'numMotor' => 'num_motor',
            'numChasis' => 'num_chasis',

            'soatAlDia' => 'soat_al_dia',
            'venceSoat' => 'vence_soat',

            'tecnicoMecanicaAlDia' => 'tecnico_mecanica_al_dia',
            'venceTecnicoMecanica' => 'vence_tecnico_mecanica',

            'nombre_cliente' => 'nombre_cliente',
            'documento_cliente' => 'documento_cliente',
            'telefono_cliene' => 'telefono_cliene',

            'clienteNombre' => 'nombre_cliente',
            'clienteDocumento' => 'documento_cliente',
            'clienteTelefono' => 'telefono_cliene',

            'comentarios_siniestros' => 'comentarios_siniestros',
            'siniestros' => 'comentarios_siniestros',
        ];

        foreach ($mapeo as $frontend => $backend) {
            if (
                $request->has($frontend) &&
                !array_key_exists($backend, $data)
            ) {
                $data[$backend] = $request->input($frontend);
            }
        }

        /*
         * Valores por defecto que ya utilizaba el controlador original.
         */
        $data['marca'] = $data['marca']
            ?? $peritaje?->marca
            ?? 'N/A';

        $data['linea'] = $data['linea']
            ?? $peritaje?->linea
            ?? 'N/A';

        $data['modelo_anio'] = $data['modelo_anio']
            ?? $peritaje?->modelo_anio
            ?? 0;

        $data['num_motor'] = $data['num_motor']
            ?? $peritaje?->num_motor
            ?? 'N/A';

        $data['num_chasis'] = $data['num_chasis']
            ?? $peritaje?->num_chasis
            ?? 'N/A';

        $data['organismo_transito'] = $data['organismo_transito']
            ?? $peritaje?->organismo_transito
            ?? 'N/A';

        $this->procesarArchivos(
            $request,
            $data,
            $peritaje
        );

        return $data;
    }

    /**
     * Procesa SOAT y revisión técnico-mecánica.
     */
    protected function procesarArchivos(
        Request $request,
        array &$data,
        ?Peritaje $peritaje = null
    ): void {

        $archivoSoat = null;

        if ($request->hasFile('archivo_soat')) {
            $archivoSoat = $request->file('archivo_soat');
        } elseif ($request->hasFile('archivoSoat')) {
            $archivoSoat = $request->file('archivoSoat');
        }

        if ($archivoSoat) {

            if ($peritaje?->archivo_soat) {
                Storage::disk('public')
                    ->delete($peritaje->archivo_soat);
            }

            $data['archivo_soat'] = $archivoSoat
                ->store('peritajes/soat', 'public');
        }

        $archivoRtm = null;

        if ($request->hasFile('archivo_tecnico_mecanica')) {
            $archivoRtm = $request->file(
                'archivo_tecnico_mecanica'
            );
        } elseif ($request->hasFile('archivoTecnicoMecanica')) {
            $archivoRtm = $request->file(
                'archivoTecnicoMecanica'
            );
        }

        if ($archivoRtm) {

            if ($peritaje?->archivo_tecnico_mecanica) {
                Storage::disk('public')
                    ->delete(
                        $peritaje->archivo_tecnico_mecanica
                    );
            }

            $data['archivo_tecnico_mecanica'] = $archivoRtm
                ->store('peritajes/rtm', 'public');
        }
    }

    /**
     * Elimina archivos físicos asociados.
     */
    protected function eliminarArchivos(Peritaje $peritaje): void
    {
        if ($peritaje->archivo_soat) {
            Storage::disk('public')
                ->delete($peritaje->archivo_soat);
        }

        if ($peritaje->archivo_tecnico_mecanica) {
            Storage::disk('public')
                ->delete(
                    $peritaje->archivo_tecnico_mecanica
                );
        }
    }

    /**
     * Comprueba permisos sobre el peritaje.
     */
    protected function verificarPermiso(Peritaje $peritaje): void
    {
        if (
            !$this->esAdmin() &&
            (string) $peritaje->inspector_id !==
            (string) auth()->id()
        ) {
            abort(
                403,
                'No tienes permiso para consultar o modificar este peritaje.'
            );
        }
    }

    /**
     * Formatea accesorios para mantener compatibilidad
     * con la respuesta que esperaba el frontend.
     */
    protected function formatearAccesorios(
        Peritaje $peritaje
    ): void {

        $peritaje->setRelation(
            'accesorios',
            $peritaje->accesorios->map(function ($item) {

                return [
                    'id' => $item->catalogoAccesorio->codigo
                        ?? $item->catalogo_accesorio_id,

                    'catalogo_accesorio_id' =>
                        $item->catalogo_accesorio_id,

                    'name' =>
                        $item->catalogoAccesorio->nombre ?? '',

                    'presente' =>
                        (bool) $item->presente,

                    'seleccion' =>
                        $item->seleccion,

                    'danado' =>
                        (bool) $item->danado,

                    'costoReparacion' =>
                        $item->costo_reparacion,

                    'comentarioDaño' =>
                        $item->comentario_dano,
                ];
            })
        );
    }

    /**
     * Formatea las imágenes para la respuesta.
     */
    protected function formatearImagenes(
        Peritaje $peritaje
    ): void {

        $peritaje->setRelation(
            'imagenes',
            $peritaje->imagenes->map(function ($img) {

                return [
                    'id' => $img->id,
                    'seccion' => $img->seccion,
                    'item_id' => $img->item_id,
                    'imagen_base64' =>
                        $img->imagen_base64,
                    'nombre_archivo' =>
                        $img->nombre_archivo,
                ];
            })
        );
    }
}
