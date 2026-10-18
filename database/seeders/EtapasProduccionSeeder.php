<?php

namespace Database\Seeders;

use App\Models\EtapaProduccion;
use Illuminate\Database\Seeder;

class EtapasProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $etapas = [
            ['codigo' => 'PLANIFICADO',   'nombre' => 'Planificado',   'orden' => 1,  'alcance' => 'plan'],
            ['codigo' => 'LANZADO',       'nombre' => 'Lanzado',       'orden' => 2,  'alcance' => 'plan'],
            ['codigo' => 'CORTE',         'nombre' => 'Corte',         'orden' => 3,  'alcance' => 'plan'],
            ['codigo' => 'PLEGADO',       'nombre' => 'Plegado',       'orden' => 4,  'alcance' => 'plan'],
            ['codigo' => 'MEZANIZADO',    'nombre' => 'Mezanizado',    'orden' => 5,  'alcance' => 'plan'],

            ['codigo' => 'ARMADO',        'nombre' => 'Armado',        'orden' => 6,  'alcance' => 'unidad'],
            ['codigo' => 'ESCORIADO',     'nombre' => 'Escoriado',     'orden' => 7,  'alcance' => 'unidad'],
            ['codigo' => 'LAVADO',        'nombre' => 'Lavado',        'orden' => 8,  'alcance' => 'unidad'],
            ['codigo' => 'SELLADO',       'nombre' => 'Sellado',       'orden' => 9,  'alcance' => 'unidad'],
            ['codigo' => 'FONDO',         'nombre' => 'Fondo',         'orden' => 10, 'alcance' => 'unidad'],
            ['codigo' => 'PINTURA',       'nombre' => 'Pintura',       'orden' => 11, 'alcance' => 'unidad'],
            ['codigo' => 'MONTAJE_FINAL', 'nombre' => 'Montaje Final', 'orden' => 12, 'alcance' => 'unidad'],
            ['codigo' => 'TERMINADO',     'nombre' => 'Terminado',     'orden' => 13, 'alcance' => 'unidad'],
            ['codigo' => 'OK_CALIDAD',    'nombre' => 'OK Calidad',    'orden' => 14, 'alcance' => 'unidad'],
            ['codigo' => 'ENTREGADO',     'nombre' => 'Entregado',     'orden' => 15, 'alcance' => 'unidad'],
        ];

        foreach ($etapas as $etapa) {
            EtapaProduccion::updateOrCreate(
                ['codigo' => $etapa['codigo']],
                [
                    'nombre' => $etapa['nombre'],
                    'orden' => $etapa['orden'],
                    'alcance' => $etapa['alcance'],
                    'activo' => true,
                ]
            );
        }
    }
}
