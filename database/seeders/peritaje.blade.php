<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #1e293b; }
        h1 { font-size: 18px; color: #080d1a; margin: 0; }
        .subtitulo { font-size: 8px; color: #64748b; margin: 2px 0; }
        .seccion-titulo { font-size: 11px; font-weight: bold; color: #2563eb; margin: 14px 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { border: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; }
        th { background: #080d1a; color: #fff; font-size: 8px; text-transform: uppercase; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 8px; }
        .ok { background: #d1fae5; color: #065f46; }
        .warn { background: #fef3c7; color: #92400e; }
        .bad { background: #fee2e2; color: #991b1b; }
        .caja { border: 1px solid #d1d5db; background: #f8fafc; border-radius: 4px; padding: 8px; }
        .footer { font-size: 7px; color: #94a3b8; margin-top: 20px; }
        .firma-img { max-height: 60px; }
    </style>
</head>
<body>

    <h1>PERITO ORINOQUIA</h1>
    <p class="subtitulo">CONSOLA DE PERITAJE TÉCNICO AUTOMOTRIZ &mdash; Sede Central: Yopal, Casanare</p>
    <p class="subtitulo">
        Inspección: <strong>{{ $p->codigo }}</strong> &mdash;
        Fecha: {{ $p->fecha_peritaje?->format('d/m/Y H:i') }} &mdash;
        Estado: <strong>{{ strtoupper($p->estado) }}</strong>
    </p>

    <div class="seccion-titulo">1. Información General del Vehículo</div>
    <table>
        <tr>
            <td><strong>Placa:</strong> {{ $p->placa }}</td>
            <td><strong>Marca / Línea:</strong> {{ $p->marca }} {{ $p->linea }}</td>
        </tr>
        <tr>
            <td><strong>Modelo / Año:</strong> {{ $p->modelo_anio }}</td>
            <td><strong>N° de Motor:</strong> {{ $p->num_motor }}</td>
        </tr>
        <tr>
            <td><strong>N° de Chasis:</strong> {{ $p->num_chasis }}</td>
            <td><strong>Organismo Tránsito:</strong> {{ $p->organismo_transito ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Kilometraje:</strong> {{ $p->kilometraje ?? 'N/A' }}</td>
            <td><strong>Tipo de Vehículo:</strong> {{ strtoupper($p->tipoVehiculo->nombre) }}</td>
        </tr>
    </table>

    <div class="seccion-titulo">2. Verificación Legal y Documental</div>
    <table>
        <thead>
        <tr><th>Documento</th><th>N° Control</th><th>Entidad Emisora</th><th>Vencimiento</th><th>Estado</th></tr>
        </thead>
        <tbody>
        <tr>
            <td>SOAT</td>
            <td>{{ $p->numero_soat ?? 'N/A' }}</td>
            <td>{{ $p->entidad_emisora_soat ?? 'N/A' }}</td>
            <td>{{ $p->vence_soat?->format('d/m/Y') ?? 'N/A' }}</td>
            <td><span class="badge {{ $p->soat_al_dia ? 'ok' : 'bad' }}">{{ $p->soat_al_dia ? 'AL DÍA' : 'VENCIDO' }}</span></td>
        </tr>
        <tr>
            <td>RTM (Tecnicomecánica)</td>
            <td>{{ $p->numero_control_rtm ?? 'N/A' }}</td>
            <td>{{ $p->cda_emisor ?? 'N/A' }}</td>
            <td>{{ $p->vence_tecnico_mecanica?->format('d/m/Y') ?? 'N/A' }}</td>
            <td><span class="badge {{ $p->tecnico_mecanica_al_dia ? 'ok' : 'bad' }}">{{ $p->tecnico_mecanica_al_dia ? 'AL DÍA' : 'VENCIDO' }}</span></td>
        </tr>
        </tbody>
    </table>
    <div class="caja">
        <strong>Alertas RUNT:</strong>
        Coincide Propietario: {{ $p->coincide_propietario_runt ? 'SÍ' : 'NO' }} |
        Embargos/Alertas: {{ $p->tiene_embargos_o_alertas ? 'SÍ' : 'NO' }} |
        Blindaje: {{ strtoupper($p->restriccion_blindaje) }}
        @if($p->comentarios_siniestros)
            <br><strong>Siniestros:</strong> {{ $p->comentarios_siniestros }}
        @endif
    </div>

    <div class="seccion-titulo">3. Motor y Transmisión</div>
    <table>
        <tr>
            <td><strong>Transmisión:</strong> {{ $p->tipo_transmision ?? 'N/A' }}</td>
            <td><strong>Estado Embrague/Caja:</strong> {{ $p->estado_transmision ?? 'N/A' }}</td>
        </tr>
        @if($p->porcentaje_bateria !== null)
        <tr>
            <td><strong>Batería (híbrido/eléctrico):</strong> {{ $p->porcentaje_bateria }}%</td>
            <td><strong>Vida útil:</strong> {{ $p->vida_util_bateria ?? 'N/A' }}</td>
        </tr>
        @endif
    </table>

    @if($p->compresionCilindros->isNotEmpty())
    <table>
        <thead><tr>
            @foreach($p->compresionCilindros as $c)<th>Cil {{ $c->numero_cilindro }}</th>@endforeach
        </tr></thead>
        <tbody><tr>
            @foreach($p->compresionCilindros as $c)<td>{{ $c->presion_psi ?? '—' }} PSI</td>@endforeach
        </tr></tbody>
    </table>
    @endif

    <table>
        <thead><tr><th>Sistema</th><th>Estado</th><th>Observaciones</th></tr></thead>
        <tbody>
        @forelse($p->sistemasMecanicos as $sm)
            <tr>
                <td>{{ $sm->catalogoSistema->nombre }}</td>
                <td>
                    <span class="badge {{ $sm->estado === 'BUENO' ? 'ok' : ($sm->estado === 'REGULAR' ? 'warn' : 'bad') }}">
                        {{ $sm->estado }}
                    </span>
                </td>
                <td>{{ $sm->observaciones ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Sin componentes evaluados.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($p->comentarios_motor)
        <p><em>Notas del inspector: {{ $p->comentarios_motor }}</em></p>
    @endif

    <div style="page-break-before: always;"></div>

    <div class="seccion-titulo">4. Inventario de Accesorios y Equipamiento</div>
    <table>
        <thead><tr><th>Elemento</th><th>Presente / Selección</th><th>Estado</th><th>Costo Rep.</th></tr></thead>
        <tbody>
        @forelse($p->accesorios as $a)
            <tr>
                <td>{{ $a->catalogoAccesorio->nombre }}</td>
                <td>{{ $a->seleccion ?? ($a->presente ? 'SÍ' : 'NO') }}</td>
                <td><span class="badge {{ $a->danado ? 'bad' : 'ok' }}">{{ $a->danado ? 'MAL ESTADO' : 'OPERATIVO' }}</span></td>
                <td>{{ $a->costo_reparacion ? '$' . number_format($a->costo_reparacion, 0, ',', '.') : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Sin accesorios registrados.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="seccion-titulo">5. Daños Externos / Carrocería</div>
    <table>
        <thead><tr><th>Pieza</th><th>Hallazgo</th><th>Micras</th><th>Comentario</th></tr></thead>
        <tbody>
        @forelse($p->danosExternos as $d)
            <tr>
                <td>{{ $d->catalogoPieza->nombre }}</td>
                <td>{{ $d->tipo_hallazgo }}</td>
                <td>{{ $d->micras ?? '—' }}</td>
                <td>{{ $d->comentario ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Sin daños externos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($p->danosInternos->isNotEmpty())
    <div class="seccion-titulo">6. Daños Internos / Cabina</div>
    <table>
        <thead><tr><th>Zona</th><th>Estado</th><th>Desgaste</th><th>Comentario</th></tr></thead>
        <tbody>
        @foreach($p->danosInternos as $d)
            <tr>
                <td>{{ $d->catalogoZona->nombre }}</td>
                <td>{{ $d->estado }}</td>
                <td>{{ $d->desgaste }}</td>
                <td>{{ $d->comentario ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    <div class="seccion-titulo">7. Detalles Técnicos</div>
    <table>
        <thead><tr><th>Elemento</th><th>Dañado</th><th>Comentario</th><th>Costo</th></tr></thead>
        <tbody>
        @forelse($p->detallesTecnicos as $dt)
            <tr>
                <td>{{ $dt->catalogoElemento->nombre }}</td>
                <td><span class="badge {{ $dt->danado ? 'bad' : 'ok' }}">{{ $dt->danado ? 'SÍ' : 'NO' }}</span></td>
                <td>{{ $dt->comentario ?? '—' }}</td>
                <td>{{ $dt->costo ? '$' . number_format($dt->costo, 0, ',', '.') : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Sin detalles técnicos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="seccion-titulo">8. Concepto Final de Evaluación</div>
    <div class="caja">
        <strong>Estado general del automotor:</strong> {{ strtoupper($p->estado_general_vehiculo) }}<br><br>
        {{ $p->concepto_final ?? 'El vehículo se encuentra en condiciones óptimas operativas de acuerdo a la documentación examinada y pruebas dinámicas de motor realizadas.' }}
        @if($p->comentarios_generales)
            <br><br><strong>Comentarios generales:</strong> {{ $p->comentarios_generales }}
        @endif
    </div>

    <table style="margin-top:8px;">
        <tr>
            <td>Puntaje Estructura: <strong>{{ $p->score_estructura }}</strong></td>
            <td>Puntaje Carrocería: <strong>{{ $p->score_carroceria }}</strong></td>
            <td>Puntaje Mecánica: <strong>{{ $p->score_mecanica }}</strong></td>
        </tr>
        <tr>
            <td>Puntaje Eléctrico: <strong>{{ $p->score_electrico }}</strong></td>
            <td>Puntaje Legal: <strong>{{ $p->score_legal }}</strong></td>
            <td>Costo total reparación: <strong>${{ number_format($p->costo_reparacion, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <div style="margin-top: 24px;">
        @if($firmaPath && file_exists($firmaPath))
            <img src="{{ $firmaPath }}" class="firma-img">
            <br>
        @endif
        <div style="border-top: 1px solid #94a3b8; width: 220px; padding-top: 4px;">
            <strong>{{ $p->inspector->name }}</strong><br>
            Firma del Inspector Autorizado &mdash; Perito Certificado, Orinoquía
        </div>
    </div>

    <p class="footer">
        Este documento es un dictamen técnico de inspección automotriz y no constituye un seguro contractual.
    </p>

</body>
</html>
