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


use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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




});



require __DIR__ . '/auth.php';
