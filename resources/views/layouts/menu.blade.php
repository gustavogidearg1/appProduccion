<h6 class="text-uppercase text-warning mb-3">
    Operación
</h6>

<nav class="nav flex-column gap-2">

    <a href="{{ route('dashboard') }}"
       class="nav-link text-white rounded
       {{ request()->routeIs('dashboard') ? 'bg-warning text-dark' : '' }}">
        <i class="bi bi-speedometer2 me-2"></i>
        Tablero
    </a>

    <a href="{{ route('produccion.movil') }}"
       class="nav-link text-white rounded
       {{ request()->routeIs('produccion.movil') ? 'bg-warning text-dark' : '' }}">
        <i class="bi bi-phone me-2"></i>
        Producción móvil
    </a>

    <a href="{{ route('planes.index') }}"
       class="nav-link text-white rounded
       {{ request()->routeIs('planes.*') ? 'bg-warning text-dark' : '' }}">
        <i class="bi bi-calendar3 me-2"></i>
        Planes de producción
    </a>

    <a href="#"
       class="nav-link text-white rounded">
        <i class="bi bi-box-seam me-2"></i>
        Chasis
    </a>

</nav>

<h6 class="text-uppercase text-warning mt-4 mb-3">
    ABM
</h6>

<nav class="nav flex-column gap-2">

    <a href="{{ route('productos.index') }}"
       class="nav-link text-white rounded
       {{ request()->routeIs('productos.*') ? 'bg-warning text-dark' : '' }}">
        <i class="bi bi-boxes me-2"></i>
        Productos
    </a>

    <a href="{{ route('clientes.index') }}"
       class="nav-link text-white rounded
       {{ request()->routeIs('clientes.*') ? 'bg-warning text-dark' : '' }}">
        <i class="bi bi-people me-2"></i>
        Clientes
    </a>

</nav>

<h6 class="text-uppercase text-warning mt-4 mb-3">
    Calidad
</h6>

<nav class="nav flex-column gap-2">

    <a href="{{ route('calidad.index') }}"
   class="nav-link text-white rounded
   {{ request()->routeIs('calidad.*') ? 'bg-warning text-dark' : '' }}">
        <i class="bi bi-check2-square me-2"></i>
        Controles de calidad
    </a>
</nav>


<h6 class="text-uppercase text-warning mt-4 mb-3">
    Ventas
</h6>
    <nav class="nav flex-column gap-2">

        <a
    href="{{ route('ventas.index') }}"
    class="nav-link text-white rounded
    {{ request()->routeIs('ventas.*') ? 'bg-warning text-dark' : '' }}"
>
    <i class="bi bi-cart me-2"></i>
    Ventas
</a>



    </nav>

<h6 class="text-uppercase text-warning mt-4 mb-3">
    Reportes
</h6>

<nav class="nav flex-column gap-2">

    <a href="#"
       class="nav-link text-white rounded">
        <i class="bi bi-bar-chart-line me-2"></i>
        Reportes
    </a>

</nav>
