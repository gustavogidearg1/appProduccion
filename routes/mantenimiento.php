<?php

use App\Http\Controllers\MantenimientoController as M;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('mantenimiento')->name('mantenimiento.')->group(function () {
    Route::get('/', [M::class,'index'])->name('index');
    Route::get('/calendario', [M::class,'calendario'])->name('calendario');
    Route::get('/exportar', [M::class,'exportar'])->name('exportar');
    Route::get('/maquinas/nueva', [M::class,'maquinaForm'])->name('maquinas.nueva');
    Route::post('/maquinas', [M::class,'maquinaGuardar'])->name('maquinas.crear');
    Route::get('/maquinas/{id}', [M::class,'maquina'])->whereNumber('id')->name('maquinas.ver');
    Route::post('/maquinas/{id}', [M::class,'maquinaGuardar'])->whereNumber('id')->name('maquinas.guardar');
    Route::post('/maquinas/{id}/tareas', [M::class,'tareaGuardar'])->whereNumber('id')->name('tareas.crear');
    Route::post('/maquinas/{id}/tareas/{tarea}', [M::class,'tareaGuardar'])->whereNumber(['id','tarea'])->name('tareas.guardar');
    Route::post('/maquinas/{id}/tareas/{tarea}/registros', [M::class,'registrar'])->whereNumber(['id','tarea'])->name('registros.crear');
    Route::post('/maquinas/{id}/registros/{registro}/anular', [M::class,'anularRegistro'])->whereNumber(['id','registro'])->name('registros.anular');
    Route::get('/ordenes', [M::class,'ordenes'])->name('ordenes');
    Route::get('/ordenes/nueva', [M::class,'ordenForm'])->name('ordenes.nueva');
    Route::post('/ordenes', [M::class,'ordenCrear'])->name('ordenes.crear');
    Route::get('/ordenes/{id}/fotos/{foto}', [M::class,'foto'])->whereNumber(['id','foto'])->name('ordenes.foto');
    Route::get('/ordenes/{id}', [M::class,'orden'])->whereNumber('id')->name('ordenes.ver');
    Route::post('/ordenes/{id}', [M::class,'ordenActualizar'])->whereNumber('id')->name('ordenes.actualizar');
});
