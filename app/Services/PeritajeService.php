<?php

namespace App\Services;

use App\Models\Peritaje;
use App\Models\PeritajeCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function esAdmin(): bool
    {
        return auth()->user()?->rol === 'admin';
    }

    public function index()
    {
        $query = Peritaje::with($this->relaciones());

        if (!$this->esAdmin()) {
            $query->where('inspector_id', auth()->id());
        }

        return $query->latest()->get();
    }

    public function show($id): Peritaje
    {
        $peritaje = Peritaje::with($this->relacionesShow())
            ->findOrFail($id);

        $this->verificarPermiso($peritaje);
        $this->formatearAccesorios($peritaje);
        $this->formatearImagenes($peritaje);

        return $peritaje;
    }

    public function store(Request $request): Peritaje
    {
        return DB::transaction(function () use ($request) {
            $data = $this->prepararDatosPrincipales($request);
            $data['inspector_id'] = auth()->id();

            $peritaje = Peritaje::create($data);

            $this->guardarServicios($peritaje, $request);

            return $peritaje->load($this->relaciones());
        });
    }

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

            $this->actualizarServicios($peritaje, $request);

            return $peritaje->load($this->relaciones());
        });
    }

    public function destroy($id): void
    {
        if (!$this->esAdmin()) {
            abort(
                403,
                'Solo los administradores pueden eliminar peritajes.'
            );
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

    public function buscarClientes(?string $query)
    {
        return $this->clienteService->buscar($query);
    }

    protected function guardarServicios(
        Peritaje $peritaje,
        Request $request
    ): void {
        $this->clienteService->guardar(
            $peritaje,
            $request->input('nombre_cliente')
                ?? $request->input('clienteNombre'),
            $request->input('documento_cliente')
                ?? $request->input('clienteDocumento'),
            $request->input('telefono_cliene')
                ?? $request->input('clienteTelefono')
        );

        $this->accesorioService->guardar(
            $peritaje,
            $request->input('accesorios')
                ?? $request->input('accesoriosList')
                ?? $request->input('peritaje_accesorios'),
            $request->input('tipo_vehiculo_id')
        );

        $this->danosService->guardarExternos(
            $peritaje,
            $request->input('danos_externos')
                ?? $request->input('danosExternos')
        );

        $this->danosService->guardarInternos(
            $peritaje,
            $request->input('danos_internos')
                ?? $request->input('danosInternos')
        );

        $this->tecnicoService->guardarDetalles(
            $peritaje,
            $request->input('detalles_tecnicos')
                ?? $request->input('detallesTecnicos')
        );

        $this->tecnicoService->guardarSistemas(
            $peritaje,
            $request->input('sistemas_mecanicos')
                ?? $request->input('sistemasMecanicos')
        );

        $this->compresionService->guardar(
            $peritaje,
            $request->input('compresion_cilindros')
                ?? $request->input('compresionCilindros')
        );

        $this->imagenService->guardar(
            $peritaje,
            $request
        );
    }

    protected function actualizarServicios(
        Peritaje $peritaje,
        Request $request
    ): void {
        $this->guardarServicios($peritaje, $request);
    }

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

    protected function procesarArchivos(
        Request $request,
        array &$data,
        ?Peritaje $peritaje = null
    ): void {
        $archivoSoat = $request->file('archivo_soat')
            ?? $request->file('archivoSoat');

        if ($archivoSoat) {
            if ($peritaje?->archivo_soat) {
                Storage::disk('public')
                    ->delete($peritaje->archivo_soat);
            }

            $data['archivo_soat'] = $archivoSoat->store(
                'peritajes/soat',
                'public'
            );
        }

        $archivoRtm = $request->file(
            'archivo_tecnico_mecanica'
        ) ?? $request->file('archivoTecnicoMecanica');

        if ($archivoRtm) {
            if ($peritaje?->archivo_tecnico_mecanica) {
                Storage::disk('public')->delete(
                    $peritaje->archivo_tecnico_mecanica
                );
            }

            $data['archivo_tecnico_mecanica'] =
                $archivoRtm->store(
                    'peritajes/rtm',
                    'public'
                );
        }
    }

    protected function eliminarArchivos(Peritaje $peritaje): void
    {
        if ($peritaje->archivo_soat) {
            Storage::disk('public')->delete(
                $peritaje->archivo_soat
            );
        }

        if ($peritaje->archivo_tecnico_mecanica) {
            Storage::disk('public')->delete(
                $peritaje->archivo_tecnico_mecanica
            );
        }
    }

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
                    'presente' => (bool) $item->presente,
                    'seleccion' => $item->seleccion,
                    'danado' => (bool) $item->danado,
                    'costoReparacion' =>
                        $item->costo_reparacion,
                    'comentarioDaño' =>
                        $item->comentario_dano,
                ];
            })
        );
    }

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
