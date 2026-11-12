<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PlanProduccionController;
use App\Http\Controllers\UnidadComponenteController;
use App\Http\Controllers\ProductoComponenteController;
use App\Http\Controllers\AvanceProduccionController;
use App\Http\Controllers\UnidadControlCalidadController;
use App\Http\Controllers\ProduccionMovilController;
use App\Http\Controllers\UnidadQrController;
use App\Http\Controllers\FamiliaControlCalidadController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaAsignacionController;


use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});



Route::get('/dashboard', [\App\Http\Controllers\TableroProduccionController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/planificacion/gantt', [\App\Http\Controllers\TableroProduccionController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('gantt.sectores');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::resource('clientes', ClienteController::class)
        ->except(['show']);

    Route::get('/planes', [PlanProduccionController::class, 'index'])
        ->name('planes.index');

    Route::get('/planes/create', [PlanProduccionController::class, 'create'])
        ->name('planes.create');

    Route::post('/planes', [PlanProduccionController::class, 'store'])
        ->name('planes.store');

    Route::get('/planes/{plan}', [PlanProduccionController::class, 'show'])
        ->name('planes.show');

    Route::post(
        '/planes/{plan}/productos/{planProducto}/generar-unidades',
        [PlanProduccionController::class, 'generarUnidades']
    )->name('planes.productos.generar-unidades');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades',
        [PlanProduccionController::class, 'unidades']
    )->name('planes.productos.unidades');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/edit',
        [PlanProduccionController::class, 'editUnidad']
    )->name('planes.productos.unidades.edit');

    Route::put(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}',
        [PlanProduccionController::class, 'updateUnidad']
    )->name('planes.productos.unidades.update');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/componentes',
        [UnidadComponenteController::class, 'index']
    )->name('planes.productos.unidades.componentes.index');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/componentes/create',
        [UnidadComponenteController::class, 'create']
    )->name('planes.productos.unidades.componentes.create');

    Route::post(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/componentes',
        [UnidadComponenteController::class, 'store']
    )->name('planes.productos.unidades.componentes.store');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/componentes/{componente}/edit',
        [UnidadComponenteController::class, 'edit']
    )->name('planes.productos.unidades.componentes.edit');

    Route::put(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/componentes/{componente}',
        [UnidadComponenteController::class, 'update']
    )->name('planes.productos.unidades.componentes.update');

    Route::get(
        '/productos/{producto}/componentes',
        [ProductoComponenteController::class, 'index']
    )->name('productos.componentes.index');

    Route::get(
        '/productos/{producto}/componentes/create',
        [ProductoComponenteController::class, 'create']
    )->name('productos.componentes.create');

    Route::post(
        '/productos/{producto}/componentes',
        [ProductoComponenteController::class, 'store']
    )->name('productos.componentes.store');

    Route::delete(
        '/productos/{producto}/componentes/{componente}',
        [ProductoComponenteController::class, 'destroy']
    )->name('productos.componentes.destroy');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/avances',
        [AvanceProduccionController::class, 'unidad']
    )->name('planes.productos.unidades.avances');

    Route::post(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/avances/{etapa}/iniciar',
        [AvanceProduccionController::class, 'iniciar']
    )->name('planes.productos.unidades.avances.iniciar');

    Route::post(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/avances/{etapa}/finalizar',
        [AvanceProduccionController::class, 'finalizar']
    )->name('planes.productos.unidades.avances.finalizar');

    Route::get(
        '/planes/{plan}/avances',
        [AvanceProduccionController::class, 'plan']
    )->name('planes.avances');

    Route::put(
        '/planes/{plan}/avances/{etapa}',
        [AvanceProduccionController::class, 'updatePlan']
    )->name('planes.avances.update');


    Route::post(
        '/planes/{plan}/avances/{etapa}/iniciar',
        [AvanceProduccionController::class, 'iniciarPlan']
    )->name('planes.avances.iniciar');

    Route::post(
        '/planes/{plan}/avances/{etapa}/finalizar',
        [AvanceProduccionController::class, 'finalizarPlan']
    )->name('planes.avances.finalizar');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/calidad',
        [UnidadControlCalidadController::class, 'index']
    )->name('planes.productos.unidades.calidad');

    Route::put(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/calidad',
        [UnidadControlCalidadController::class, 'update']
    )->name('planes.productos.unidades.calidad.update');

    Route::get(
        '/produccion/movil',
        [ProduccionMovilController::class, 'index']
    )->name('produccion.movil');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}/qr',
        [UnidadQrController::class, 'show']
    )->name('planes.productos.unidades.qr');

    Route::get(
        '/planes/{plan}/productos/{planProducto}/unidades/{unidad}',
        [PlanProduccionController::class, 'showUnidad']
    )->name('planes.productos.unidades.show');

    Route::get('/calidad', [
        FamiliaControlCalidadController::class,
        'familias',
    ])->name('calidad.index');

    Route::prefix('calidad/familias/{familia}/controles')
        ->name('calidad.familias.controles.')
        ->group(function () {
            Route::get('/', [
                FamiliaControlCalidadController::class,
                'index',
            ])->name('index');

            Route::get('/create', [
                FamiliaControlCalidadController::class,
                'create',
            ])->name('create');

            Route::post('/', [
                FamiliaControlCalidadController::class,
                'store',
            ])->name('store');

            Route::get('/{familiaControl}/edit', [
                FamiliaControlCalidadController::class,
                'edit',
            ])->name('edit');

            Route::put('/{familiaControl}', [
                FamiliaControlCalidadController::class,
                'update',
            ])->name('update');

            Route::delete('/{familiaControl}', [
                FamiliaControlCalidadController::class,
                'destroy',
            ])->name('destroy');
        });

    Route::get(
        '/ventas',
        [VentaController::class, 'index']
    )->name('ventas.index');

    Route::get(
        '/ventas/create',
        [VentaController::class, 'create']
    )->name('ventas.create');

    Route::post(
        '/ventas',
        [VentaController::class, 'store']
    )->name('ventas.store');

    Route::get(
        '/ventas/{venta}',
        [VentaController::class, 'show']
    )->name('ventas.show');

    Route::get(
        '/ventas/detalles/{ventaDetalle}/asignar',
        [VentaAsignacionController::class, 'create']
    )->name('ventas.detalles.asignar');

    Route::post(
        '/ventas/detalles/{ventaDetalle}/asignar',
        [VentaAsignacionController::class, 'store']
    )->name('ventas.detalles.asignar.store');

    Route::get(
        '/ventas/{venta}/edit',
        [VentaController::class, 'edit']
    )->name('ventas.edit');

    Route::put(
        '/ventas/{venta}',
        [VentaController::class, 'update']
    )->name('ventas.update');

    Route::post(
        '/ventas/{venta}/anular',
        [VentaController::class, 'anular']
    )->name('ventas.anular');

    Route::get(
        '/ventas/{venta}/productos/agregar',
        [VentaController::class, 'agregarProducto']
    )->name('ventas.productos.agregar');

    Route::post(
        '/ventas/{venta}/productos',
        [VentaController::class, 'guardarProducto']
    )->name('ventas.productos.guardar');
});




Route::middleware('auth')->group(function () {
    Route::get('/configuracion/plazos', [\App\Http\Controllers\PlazoEntregaController::class,'index'])->name('plazos.index');
    Route::post('/configuracion/plazos', [\App\Http\Controllers\PlazoEntregaController::class,'guardar'])->name('plazos.guardar');
    Route::post('/configuracion/calendario', [\App\Http\Controllers\PlazoEntregaController::class,'calendario'])->name('plazos.calendario');
    Route::post('/configuracion/calendario/{cierre}/alternar', [\App\Http\Controllers\PlazoEntregaController::class,'alternar'])->name('plazos.calendario.alternar');
    Route::get('/planes/{plan}/demoras', [\App\Http\Controllers\DemoraProduccionController::class,'index'])->name('demoras.index');
    Route::post('/planes/{plan}/demoras', [\App\Http\Controllers\DemoraProduccionController::class,'guardar'])->name('demoras.guardar');
    Route::post('/planes/{plan}/demoras/{demora}/resolver', [\App\Http\Controllers\DemoraProduccionController::class,'resolver'])->name('demoras.resolver');
    Route::get('/planes/{plan}/estado', [\App\Http\Controllers\PlanEstadoController::class,'index'])->name('planes.estado.index');
    Route::put('/planes/{plan}/estado', [\App\Http\Controllers\PlanEstadoController::class,'update'])->name('planes.estado.update');
    Route::put('/planes/{plan}/avances/{etapa}/fechas-reales', [\App\Http\Controllers\AvanceProduccionController::class,'corregirFechasPlan'])->name('planes.avances.reales');
    Route::get('/planes/{plan}/productos/{planProducto}/unidades/{unidad}/avances/{etapa}/corregir', [\App\Http\Controllers\AvanceCorreccionController::class,'edit'])->name('avances.correccion.edit');
    Route::put('/planes/{plan}/productos/{planProducto}/unidades/{unidad}/avances/{etapa}/corregir', [\App\Http\Controllers\AvanceCorreccionController::class,'update'])->name('avances.correccion.update');
    Route::get('/unidades/{unidad}/entrega', [\App\Http\Controllers\EntregaUnidadController::class,'index'])->name('entregas.index');
    Route::post('/unidades/{unidad}/entrega/corregir-anterior', [\App\Http\Controllers\EntregaUnidadController::class,'corregirAnterior'])->name('entregas.corregir-anterior');
    Route::post('/unidades/{unidad}/entrega', [\App\Http\Controllers\EntregaUnidadController::class,'guardar'])->name('entregas.guardar');
    Route::post('/unidades/{unidad}/entrega/fotos', [\App\Http\Controllers\EntregaUnidadController::class,'agregarFotos'])->name('entregas.fotos');
    Route::get('/unidades/{unidad}/entrega/fotos/{foto}', [\App\Http\Controllers\EntregaUnidadController::class,'foto'])->name('entregas.foto');
});

require __DIR__ . '/auth.php';

Route::middleware('auth')->group(function(){
 Route::get('/calidad/familias/{familia}/planilla',[\App\Http\Controllers\PlanillaCalidadController::class,'index'])->name('calidad.documentos.index');
 Route::post('/calidad/familias/{familia}/planilla',[\App\Http\Controllers\PlanillaCalidadController::class,'crear'])->name('calidad.documentos.crear');
 Route::post('/calidad/familias/{familia}/planilla/nueva',[\App\Http\Controllers\PlanillaCalidadController::class,'nueva'])->name('calidad.documentos.nueva');
 Route::get('/calidad/familias/{familia}/planilla/{revision}',[\App\Http\Controllers\PlanillaCalidadController::class,'edit'])->name('calidad.documentos.edit');
 Route::post('/calidad/familias/{familia}/planilla/{revision}',[\App\Http\Controllers\PlanillaCalidadController::class,'guardar'])->name('calidad.documentos.guardar');
 Route::post('/calidad/familias/{familia}/planilla/{revision}/publicar',[\App\Http\Controllers\PlanillaCalidadController::class,'publicar'])->name('calidad.documentos.publicar');
 Route::get('/calidad/familias/{familia}/planilla/{revision}/imprimir',[\App\Http\Controllers\PlanillaCalidadController::class,'imprimir'])->name('calidad.documentos.imprimir');
 Route::get('/calidad/planillas/{revision}/imagenes/{indice}',[\App\Http\Controllers\PlanillaCalidadController::class,'imagen'])->name('calidad.documentos.imagen');
 Route::get('/unidades/{unidad}/calidad/imprimir',[\App\Http\Controllers\PlanillaCalidadController::class,'registro'])->name('calidad.documentos.registro');
 Route::post('/unidades/{unidad}/calidad/revision',[\App\Http\Controllers\PlanillaCalidadController::class,'adoptar'])->name('calidad.documentos.adoptar');
 Route::get('/unidades/{unidad}/calidad/historial/{historial}/evidencia/{lado}',[\App\Http\Controllers\UnidadControlCalidadController::class,'evidenciaHistorica'])->name('calidad.evidencia.historial');
 Route::get('/unidades/{unidad}/calidad/evidencia/{controlUnidad}',[\App\Http\Controllers\UnidadControlCalidadController::class,'evidencia'])->name('calidad.evidencia');
});

Route::middleware('auth')->post('/calidad/familias/{familia}/editar-item/{control}',[\App\Http\Controllers\PlanillaCalidadController::class,'editarItem'])->name('calidad.documentos.editarItem');

Route::middleware('auth')->group(function(){
 Route::get('/calidad/secciones',[\App\Http\Controllers\ConfiguracionCalidadController::class,'secciones'])->name('calidad.secciones');
 Route::post('/calidad/familias/{familia}/secciones',[\App\Http\Controllers\ConfiguracionCalidadController::class,'seccionGuardar'])->name('calidad.secciones.guardar');
 Route::get('/calidad/familias/{familia}/puntos/nuevo',[\App\Http\Controllers\ConfiguracionCalidadController::class,'edit'])->name('calidad.configuracion.nuevo');
 Route::post('/calidad/familias/{familia}/puntos',[\App\Http\Controllers\ConfiguracionCalidadController::class,'guardar'])->name('calidad.configuracion.crear');
 Route::get('/calidad/familias/{familia}/puntos/{indice}/editar',[\App\Http\Controllers\ConfiguracionCalidadController::class,'edit'])->name('calidad.configuracion.editar');
 Route::post('/calidad/familias/{familia}/puntos/{indice}',[\App\Http\Controllers\ConfiguracionCalidadController::class,'guardar'])->name('calidad.configuracion.guardar');
});

Route::middleware('auth')->prefix('calidad/hallazgos')->name('hallazgos.')->group(function () {
    Route::get('/', [\App\Http\Controllers\HallazgoCalidadController::class, 'index'])->name('index');
    Route::get('/nuevo', [\App\Http\Controllers\HallazgoCalidadController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\HallazgoCalidadController::class, 'store'])->name('store');
    Route::get('/{hallazgo}', [\App\Http\Controllers\HallazgoCalidadController::class, 'show'])->whereNumber('hallazgo')->name('show');
    Route::post('/{hallazgo}/seguimiento', [\App\Http\Controllers\HallazgoCalidadController::class, 'actualizar'])->whereNumber('hallazgo')->name('actualizar');
    Route::get('/{hallazgo}/fotos/{movimiento}', [\App\Http\Controllers\HallazgoCalidadController::class, 'foto'])->whereNumber(['hallazgo', 'movimiento'])->name('foto');
});