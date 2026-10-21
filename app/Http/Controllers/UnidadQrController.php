<?php

namespace App\Http\Controllers;

use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\UnidadProduccion;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

class UnidadQrController extends Controller
{
    public function show(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        if (
            $planProducto->plan_produccion_id !== $plan->id ||
            $unidad->plan_producto_id !== $planProducto->id
        ) {
            abort(404);
        }

        $url = route('produccion.movil') .
            '?plan=' . urlencode((string) $plan->numero) .
            '&chasis=' . urlencode((string) $unidad->chasis);

        $resultado = (new Builder(
            writer: new SvgWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 20,
        ))->build();

        return response($resultado->getString())
            ->header('Content-Type', $resultado->getMimeType());
    }
}

