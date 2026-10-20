<?php

namespace Database\Seeders;

use App\Models\ControlCalidad;
use App\Models\ProductoControlCalidad;
use Illuminate\Database\Seeder;

class ProductoControlesCalidadSeeder extends Seeder
{
    public function run(): void
    {
        $productoId = 3;

        $controles = ControlCalidad::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        foreach ($controles as $control) {
            ProductoControlCalidad::updateOrCreate(
                [
                    'producto_id' => $productoId,
                    'control_calidad_id' => $control->id,
                ],
                [
                    'orden' => $control->orden,
                    'obligatorio' => $control->obligatorio,
                    'activo' => true,
                ]
            );
        }
    }
}
