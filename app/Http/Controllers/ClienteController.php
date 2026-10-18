<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $clientes = Cliente::query()
            ->when($search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('cuit', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('abm.clientes.index', compact('clientes', 'search'));
    }

    public function create()
    {
        return view('abm.clientes.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'cuit' => 'nullable|string|max:13|unique:clientes,cuit',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
            'observaciones' => 'nullable|string',
        ]);

        $datos['activo'] = $request->boolean('activo', true);

        Cliente::create($datos);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente)
    {
        return view('abm.clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'cuit' => [
                'nullable',
                'string',
                'max:13',
                Rule::unique('clientes', 'cuit')->ignore($cliente->id),
            ],
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
            'observaciones' => 'nullable|string',
        ]);

        $datos['activo'] = $request->boolean('activo');

        $cliente->update($datos);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()
            ->route('abm.clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
