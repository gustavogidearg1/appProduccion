<?php

namespace Database\Seeders;

use App\Models\ControlCalidad;
use Illuminate\Database\Seeder;

class ControlesCalidadSeeder extends Seeder
{
    public function run(): void
    {
        $controles = [
            [
                'nombre' => 'Identificación del chasis',
                'descripcion' => 'Verificar que el número de chasis sea correcto y legible.',
                'tipo_respuesta' => 'texto',
                'orden' => 1,
            ],
            [
                'nombre' => 'Soldaduras verificadas',
                'descripcion' => 'Control visual de soldaduras y uniones estructurales.',
                'tipo_respuesta' => 'aprobado',
                'orden' => 2,
            ],
            [
                'nombre' => 'Pintura sin defectos',
                'descripcion' => 'Verificar que la pintura no tenga golpes, rayas ni defectos visibles.',
                'tipo_respuesta' => 'aprobado',
                'orden' => 3,
            ],
            [
                'nombre' => 'Neumáticos instalados',
                'descripcion' => 'Verificar instalación y estado de los neumáticos.',
                'tipo_respuesta' => 'aprobado',
                'orden' => 4,
            ],
            [
                'nombre' => 'Accesorios instalados',
                'descripcion' => 'Verificar los accesorios definidos para la unidad.',
                'tipo_respuesta' => 'aprobado',
                'orden' => 5,
            ],
            [
                'nombre' => 'Trazabilidad de accesorios',
                'descripcion' => 'Registrar los números de trazabilidad de los accesorios instalados.',
                'tipo_respuesta' => 'texto',
                'orden' => 6,
            ],
            [
                'nombre' => 'Funcionamiento general',
                'descripcion' => 'Verificar el funcionamiento general del implemento.',
                'tipo_respuesta' => 'aprobado',
                'orden' => 7,
            ],
            [
                'nombre' => 'Observaciones finales',
                'descripcion' => 'Registrar cualquier observación del control final.',
                'tipo_respuesta' => 'texto',
                'orden' => 8,
                'obligatorio' => false,
            ],
        ];

        foreach ($controles as $control) {
            ControlCalidad::updateOrCreate(
                [
                    'nombre' => $control['nombre'],
                ],
                [
                    'descripcion' => $control['descripcion'],
                    'tipo_respuesta' => $control['tipo_respuesta'],
                    'orden' => $control['orden'],
                    'obligatorio' => $control['obligatorio'] ?? true,
                    'activo' => true,
                ]
            );
        }
    }
}
