<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('peritajes', 'compresion_cilindros')) {
            return;
        }

        DB::table('peritajes')
            ->select('id', 'compresion_cilindros')
            ->whereNotNull('compresion_cilindros')
            ->orderBy('id')
            ->chunk(100, function ($peritajes) {
                foreach ($peritajes as $peritaje) {
                    $lecturas = is_string($peritaje->compresion_cilindros)
                        ? json_decode($peritaje->compresion_cilindros, true)
                        : $peritaje->compresion_cilindros;

                    if (!is_array($lecturas)) {
                        continue;
                    }

                    foreach ([1, 2, 3, 4] as $numero) {
                        $lectura = $lecturas[$numero]
                            ?? $lecturas[(string) $numero]
                            ?? $lecturas[$numero - 1]
                            ?? null;

                        if ($lectura === null) {
                            continue;
                        }

                        if (!is_array($lectura)) {
                            $lectura = ['presion_psi' => $lectura];
                        }

                        $presion = $lectura['presion_psi']
                            ?? $lectura['valor']
                            ?? $lectura['psi']
                            ?? null;

                        $fuga = $lectura['fuga_porcentaje']
                            ?? $lectura['fuga']
                            ?? $lectura['porcentaje']
                            ?? null;

                        if (($presion === null || $presion === '') && ($fuga === null || $fuga === '')) {
                            continue;
                        }

                        DB::table('peritaje_compresion_cilindros')->updateOrInsert(
                            [
                                'peritaje_id' => $peritaje->id,
                                'numero_cilindro' => $numero,
                            ],
                            [
                                'presion_psi' => $presion === '' ? null : $presion,
                                'fuga_porcentaje' => $fuga === '' ? null : $fuga,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }
                }
            });

        Schema::table('peritajes', function (Blueprint $table) {
            $table->dropColumn('compresion_cilindros');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('peritajes', 'compresion_cilindros')) {
            Schema::table('peritajes', function (Blueprint $table) {
                $table->json('compresion_cilindros')->nullable();
            });
        }
    }
};
