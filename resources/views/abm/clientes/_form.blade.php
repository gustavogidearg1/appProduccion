@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">

    <div class="col-md-8">
        <label for="nombre" class="form-label">Nombre o razón social</label>
        <input
            type="text"
            class="form-control"
            id="nombre"
            name="nombre"
            value="{{ old('nombre', $cliente->nombre ?? '') }}"
            required
        >
    </div>

    <div class="col-md-4">
        <label for="cuit" class="form-label">CUIT</label>
        <input
            type="text"
            class="form-control"
            id="cuit"
            name="cuit"
            value="{{ old('cuit', $cliente->cuit ?? '') }}"
            maxlength="13"
        >
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            value="{{ old('email', $cliente->email ?? '') }}"
        >
    </div>

    <div class="col-md-6">
        <label for="telefono" class="form-label">Teléfono</label>
        <input
            type="text"
            class="form-control"
            id="telefono"
            name="telefono"
            value="{{ old('telefono', $cliente->telefono ?? '') }}"
        >
    </div>

    <div class="col-12">
        <label for="direccion" class="form-label">Dirección</label>
        <input
            type="text"
            class="form-control"
            id="direccion"
            name="direccion"
            value="{{ old('direccion', $cliente->direccion ?? '') }}"
        >
    </div>

    <div class="col-12">
        <label for="observaciones" class="form-label">Observaciones</label>
        <textarea
            class="form-control"
            id="observaciones"
            name="observaciones"
            rows="3"
        >{{ old('observaciones', $cliente->observaciones ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <input type="hidden" name="activo" value="0">

        <div class="form-check">
            <input
                class="form-check-input"
                type="checkbox"
                id="activo"
                name="activo"
                value="1"
                @checked(old('activo', $cliente->activo ?? true))
            >
            <label class="form-check-label" for="activo">
                Cliente activo
            </label>
        </div>
    </div>

</div>
