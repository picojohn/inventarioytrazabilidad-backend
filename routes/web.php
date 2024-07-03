<?php

use App\Http\Controllers\Pedidos;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Parametrizacion;
use App\Http\Controllers\SelloBitacoraController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(["prefix" => "productos-clientes"], function(){
    Route::get('/', [Parametrizacion\ProductoClienteController::class,'productoClienteExport'])->name('productos-clientes.productoClienteExport');
        // ->middleware(['permission:ListarColaborador']);
});
Route::group(["prefix" => "exportar-inventario"], function(){
    Route::get('/', [Pedidos\SelloController::class,'exportarInventario'])->name('exportar-inventario.exportarInventario');
        // ->middleware(['permission:ListarColaborador']);
});
Route::group(["prefix" => "bitacora-sellos"], function(){
    Route::get('/', [SelloBitacoraController::class,'exportarBitacora'])->name('bitacora-sellos.exportarBitacora');
        // ->middleware(['permission:ListarColaborador']);
});
