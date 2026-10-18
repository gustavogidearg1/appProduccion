<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoComponente;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductoComponenteController extends Controller
{
    public function index(Producto $producto)
    {
        $producto->load([
            'componentesEstandar.componenteProducto',
        ]);

        return view(
            'abm.productos.componentes.index',
            compact('producto')
        );
    }

    public function create(Producto $producto)
    {
        $componentes = Producto::query()
            ->where('activo', true)
            ->where('id', '!=', $producto->id)
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
            ]);

        return view(
            'abm.productos.componentes.create',
            compact('producto', 'componentes')
        );
    }

    public function store(Request $request, Producto $producto)
    {
        $datos = $request->validate([
            'componente_producto_id' => [
                'required',
                'integer',
            ],

            'cantidad' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'obligatorio' => [
                'required',
                'boolean',
            ],

            'activo' => [
                'required',
                'boolean',
            ],

            'uso' => [
                'required',
                'in:definitivo,exhibicion',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],
        ]);

        if ((int) $datos['componente_producto_id'] === (int) $producto->id) {
            throw ValidationException::withMessages([
                'componente_producto_id' =>
                    'El producto no puede ser componente de sí mismo.',
            ]);
        }

        Producto::query()
            ->where('activo', true)
            ->findOrFail($datos['componente_producto_id']);

        $yaExiste = ProductoComponente::query()
            ->where('producto_id', $producto->id)
            ->where(
                'componente_producto_id',
                $datos['componente_producto_id']
            )
            ->exists();

        if ($yaExiste) {
            throw ValidationException::withMessages([
                'componente_producto_id' =>
                    'Este componente ya está configurado para el producto.',
            ]);
        }

        ProductoComponente::create([
            'producto_id' => $producto->id,
            'componente_producto_id' => $datos['componente_producto_id'],
            'cantidad' => $datos['cantidad'],
            'obligatorio' => $datos['obligatorio'],
            'activo' => $datos['activo'],
            'uso' => $datos['uso'],
            'observaciones' => $datos['observaciones'] ?? null,
        ]);

        return redirect()
            ->route(
                'productos.componentes.index',
                $producto
            )
            ->with(
                'success',
                'Componente estándar agregado correctamente.'
            );
    }

    public function destroy(
        Producto $producto,
        ProductoComponente $componente
    ) {
        if ($componente->producto_id !== $producto->id) {
            abort(404);
        }

        $componente->delete();

        return back()->with(
            'success',
            'Componente estándar eliminado correctamente.'
        );
    }
}
