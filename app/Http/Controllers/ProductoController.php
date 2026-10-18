<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $productos = Producto::with('familia', 'tipo')
            ->where('activo', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('abm.productos.index', compact('productos', 'search'));
    }
}
