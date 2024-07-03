<?php

use App\Http\Controllers\Pedidos;
use App\Http\Controllers\Seguridad;
use App\Http\Controllers\Remisiones;
use App\Http\Controllers\Operaciones;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Parametrizacion;
use App\Http\Controllers\SelloBitacoraController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/users/token', [UserController::class,'getToken']);
Route::post('/forgot-password', [UserController::class,'forgotPassword'])->middleware('guest')->name('password.email');
Route::post('/reset-password',[UserController::class,'resetPassword'])->middleware('guest')->name('password.update');
Route::post('/register', [UserController::class, 'register'])->name('register.api');
Route::post('/login', [UserController::class, 'login'])->name('login.api');

Route::group(['middleware' => ['auth:api']], function (){
    // User
    Route::group(["prefix" => "users"],function(){
        Route::get('current/session',  [UserController::class,'getSession'])->name('session.show');
    });

    // ---------------------- Seguridad -------------------------- //

    // Usuarios
    Route::group(["prefix" => "usuarios"],function(){
        Route::get('/', [Seguridad\UsuarioController::class,'index'])->name('usuarios.index');
        Route::post('/', [Seguridad\UsuarioController::class,'store'])->name('usuarios.store');
            // ->middleware(['permission:CrearUsuario']);
        Route::get('/{id}', [Seguridad\UsuarioController::class,'show'])->name('usuarios.show');
            // ->middleware(['permission:ListarUsuario']);
        Route::put('/cambio-clave', [Seguridad\UsuarioController::class,'changePassword'])->name('usuarios.changePassword');
        Route::put('/{id}', [Seguridad\UsuarioController::class,'update'])->name('usuarios.update');
            // ->middleware(['permission:ModificarUsuario']);
        Route::delete('/{id}', [Seguridad\UsuarioController::class,'destroy'])->name('usuarios.delete');
            // ->middleware(['permission:EliminarUsuario']);
    });

    // Roles
    Route::group(["prefix" => "roles"],function(){
        Route::get('/', [Seguridad\RolController::class,'index'])->name('roles.index');
        Route::get('/permisos/{id}', [Seguridad\RolController::class,'obtenerPermisos'])->name('roles.permisos');
            // ->middleware(['permission:PermitirRol']);
        Route::post('/permisos', [Seguridad\RolController::class,'otorgarPermisos'])->name('roles.otorgarPermisos');
            // ->middleware(['permission:PermitirRol']);
        Route::put('/permisos', [Seguridad\RolController::class,'revocarPermisos'])->name('roles.revocarPermisos');
            // ->middleware(['permission:PermitirRol']);
        Route::post('/', [Seguridad\RolController::class,'store'])->name('roles.store');
            // ->middleware(['permission:CrearRol']);
        Route::get('/{id}', [Seguridad\RolController::class,'show'])->name('roles.show');
            // ->middleware(['permission:ListarRol']);
        Route::put('/{id}', [Seguridad\RolController::class,'update'])->name('roles.update');
            // ->middleware(['permission:ModificarRol']);
        Route::delete('/{id}', [Seguridad\RolController::class,'destroy'])->name('roles.delete');
            // ->middleware(['permission:EliminarRol']);
    });

    // Aplicaciones
    Route::group(["prefix" => "aplicaciones"],function(){
        Route::get('/', [Seguridad\AplicacionController::class,'index'])->name('aplicaciones.index');
        Route::post('/', [Seguridad\AplicacionController::class,'store'])->name('aplicaciones.store');
            // ->middleware(['permission:CrearAplicacion']);
        Route::get('/{id}', [Seguridad\AplicacionController::class,'show'])->name('aplicaciones.show');
            // ->middleware(['permission:ListarAplicacion']);
        Route::put('/{id}', [Seguridad\AplicacionController::class,'update'])->name('aplicaciones.update');
            // ->middleware(['permission:ModificarAplicacion']);
        Route::delete('/{id}', [Seguridad\AplicacionController::class,'destroy'])->name('aplicaciones.delete');
            // ->middleware(['permission:EliminarAplicacion']);
    });

    // Módulos
    Route::group(["prefix" => "modulos"],function(){
        Route::get('/', [Seguridad\ModuloController::class,'index'])->name('modulos.index');
        Route::post('/', [Seguridad\ModuloController::class,'store'])->name('modulos.store');
            // ->middleware(['permission:CrearModulo']);
        Route::get('/{id}', [Seguridad\ModuloController::class,'show'])->name('modulos.show');
            // ->middleware(['permission:ListarModulo']);
        Route::put('/{id}', [Seguridad\ModuloController::class,'update'])->name('modulos.update');
            // ->middleware(['permission:ModificarModulo']);
        Route::delete('/{id}', [Seguridad\ModuloController::class,'destroy'])->name('modulos.delete');
            // ->middleware(['permission:EliminarModulo']);
    });

    // Opciones del Sistema
    Route::group(["prefix" => "opciones-del-sistema"],function(){
        Route::get('/', [Seguridad\OpcionSistemaController::class,'index'])->name('opciones-del-sistema.index');
        Route::post('/', [Seguridad\OpcionSistemaController::class,'store'])->name('opciones-del-sistema.store');
            // ->middleware(['permission:CrearOpcionSistema']);
        Route::get('/{id}', [Seguridad\OpcionSistemaController::class,'show'])->name('opciones-del-sistema.show');
            // ->middleware(['permission:ListarOpcionSistema']);
        Route::put('/{id}', [Seguridad\OpcionSistemaController::class,'update'])->name('opciones-del-sistema.update');
            // ->middleware(['permission:ModificarOpcionSistema']);
        Route::delete('/{id}', [Seguridad\OpcionSistemaController::class,'destroy'])->name('opciones-del-sistema.delete');
            // ->middleware(['permission:EliminarOpcionSistema']);
    });

    // Permisos
    Route::group(["prefix" => "permisos"],function(){
        Route::get('/', [Seguridad\PermisoController::class,'index'])->name('permisos.index');
        Route::post('/', [Seguridad\PermisoController::class,'store'])->name('permisos.store');
            // ->middleware(['permission:CrearAccionPermiso']);
        Route::get('/{id}', [Seguridad\PermisoController::class,'show'])->name('permisos.show');
            // ->middleware(['permission:ListarAccionPermiso']);
        Route::put('/{id}', [Seguridad\PermisoController::class,'update'])->name('permisos.update');
            // ->middleware(['permission:ModificarAccionPermiso']);
        Route::delete('/{id}', [Seguridad\PermisoController::class,'destroy'])->name('permisos.delete');
            // ->middleware(['permission:EliminarAccionPermiso']);
    });

    // Auditoria Tablas
    Route::group(["prefix" => "auditoria-tablas"],function(){
        Route::get('/', [Seguridad\AuditoriaTablaController::class,'index'])->name('auditoria-tablas.index');
            // ->middleware(['permission:ListarAuditorias']);
    });

    // solicitudes acceso
    Route::group(["prefix" => "solicitudes-acceso"],function(){
        Route::get('/', [Seguridad\SolicitudAccesoController::class,'index'])->name('solicitudes-acceso.index');
        Route::get('/{id}', [Seguridad\SolicitudAccesoController::class,'show'])->name('solicitudes-acceso.show');
            // ->middleware(['permission:ListarUsuario']);
        Route::put('/{id}', [Seguridad\SolicitudAccesoController::class,'update'])->name('solicitudes-acceso.update');
            // ->middleware(['permission:ModificarUsuario']);
        Route::delete('/{id}', [Seguridad\SolicitudAccesoController::class,'destroy'])->name('solicitudes-acceso.delete');
            // ->middleware(['permission:EliminarUsuario']);
    });
    Route::get('/solicitudes-acceso-consulta/{usuario_id}', [Seguridad\SolicitudAccesoController::class,'consultar'])->name('solicitudes-acceso.consultar');

    // ---------------------- Parametrizacion -------------------------- //
    // Tipos Asesorias
    Route::group(["prefix" => "tipos-alerta"],function(){
        Route::get('/', [Parametrizacion\TipoAlertaController::class,'index'])->name('tipos-alerta.index');
        Route::post('/', [Parametrizacion\TipoAlertaController::class,'store'])->name('tipos-alerta.store');
            // ->middleware(['permission:CrearAplicacion']);
        Route::get('/{id}', [Parametrizacion\TipoAlertaController::class,'show'])->name('tipos-alerta.show');
            // ->middleware(['permission:ListarAplicacion']);
        Route::put('/{id}', [Parametrizacion\TipoAlertaController::class,'update'])->name('tipos-alerta.update');
            // ->middleware(['permission:ModificarAplicacion']);
        Route::delete('/{id}', [Parametrizacion\TipoAlertaController::class,'destroy'])->name('tipos-alerta.delete');
            // ->middleware(['permission:EliminarAplicacion']);
    });

    // Parametros correos
    Route::group(["prefix" => "parametros-correo"],function(){
        Route::get('/', [Parametrizacion\ParametroCorreoController::class,'index'])->name('parametros_correo.index');
        Route::post('/', [Parametrizacion\ParametroCorreoController::class,'store'])->name('parametros_correo.store');
            // ->middleware(['permission:CrearAplicacion']);
        Route::get('/{id}', [Parametrizacion\ParametroCorreoController::class,'show'])->name('parametros_correo.show');
            // ->middleware(['permission:ListarAplicacion']);
        Route::put('/{id}', [Parametrizacion\ParametroCorreoController::class,'update'])->name('parametros_correo.update');
            // ->middleware(['permission:ModificarAplicacion']);
        Route::delete('/{id}', [Parametrizacion\ParametroCorreoController::class,'destroy'])->name('parametros_correo.delete');
            // ->middleware(['permission:EliminarAplicacion']);
    });

    // Parametros Constantes
    Route::group(["prefix" => "parametros-constantes"],function(){
        Route::get('/', [Parametrizacion\ParametroConstanteController::class,'index'])->name('parametros-constantes.index');
        Route::post('/', [Parametrizacion\ParametroConstanteController::class,'store'])->name('parametros-constantes.store');
            // ->middleware(['permission:CrearParametroConstante']);
        Route::get('/consultar', [Parametrizacion\ParametroConstanteController::class,'consultar'])->name('parametros-constantes.consultar');
        Route::get('/consultar-lugar-interno', [Parametrizacion\ParametroConstanteController::class,'consultarLugarInterno'])->name('parametros-constantes.consultarLugarInterno');
            // ->middleware(['permission:EliminarParametroConstante']);
        Route::get('/tipos-rol', [Parametrizacion\ParametroConstanteController::class,'tiposRol'])->name('parametros-constantes.tiposRol');
            // ->middleware(['permission:EliminarParametroConstante']);
        Route::get('/{id}', [Parametrizacion\ParametroConstanteController::class,'show'])->name('parametros-constantes.show');
            // ->middleware(['permission:ListarParametroConstante']);
        Route::put('/{id}', [Parametrizacion\ParametroConstanteController::class,'update'])->name('parametros-constantes.update');
            // ->middleware(['permission:ModificarParametroConstante']);
        Route::delete('/{id}', [Parametrizacion\ParametroConstanteController::class,'destroy'])->name('parametros-constantes.delete');
            // ->middleware(['permission:EliminarParametroConstante']);
    });

    // Zonas Contenedores
    Route::group(["prefix" => "zonas-contenedores"],function(){
        Route::get('/', [Parametrizacion\ZonaContenedorController::class,'index'])->name('zonas-contenedores.index');
        Route::post('/', [Parametrizacion\ZonaContenedorController::class,'store'])->name('zonas-contenedores.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\ZonaContenedorController::class,'show'])->name('zonas-contenedores.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\ZonaContenedorController::class,'update'])->name('zonas-contenedores.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\ZonaContenedorController::class,'destroy'])->name('zonas-contenedores.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Cliente
    Route::group(["prefix" => "clientes"],function(){
        Route::get('/', [Parametrizacion\ClienteController::class,'index'])->name('clientes.index');
        Route::post('/', [Parametrizacion\ClienteController::class,'store'])->name('clientes.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\ClienteController::class,'show'])->name('clientes.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\ClienteController::class,'update'])->name('clientes.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\ClienteController::class,'destroy'])->name('clientes.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Asociado
    Route::group(["prefix" => "asociados"],function(){
        Route::get('/', [Parametrizacion\AsociadoController::class,'index'])->name('asociados.index');
    });

    // Colores
    Route::group(["prefix" => "colores"],function(){
        Route::get('/', [Parametrizacion\ColorController::class,'index'])->name('colores.index');
    });

    // Producto
    Route::group(["prefix" => "productos"],function(){
        Route::get('/', [Parametrizacion\ProductoController::class,'index'])->name('productos.index');
    });

    // Tipos Documento
    Route::group(["prefix" => "tipos-documento"],function(){
        Route::get('/', [Parametrizacion\TipoDocumentoController::class,'index'])->name('tipos-documento.index');
    });

    // Productos Cliente
    Route::group(["prefix" => "productos-clientes"],function(){
        Route::get('/', [Parametrizacion\ProductoClienteController::class,'index'])->name('productos-clientes.index');
        Route::post('/', [Parametrizacion\ProductoClienteController::class,'store'])->name('productos-clientes.store');
            // ->middleware(['permission:CrearLugar']);
        Route::get('/{id}', [Parametrizacion\ProductoClienteController::class,'show'])->name('productos-clientes.show');
            // ->middleware(['permission:ListarLugar']);
        Route::put('/{id}', [Parametrizacion\ProductoClienteController::class,'update'])->name('productos-clientes.update');
            // ->middleware(['permission:ModificarLugar']);
        Route::delete('/{id}', [Parametrizacion\ProductoClienteController::class,'destroy'])->name('productos-clientes.delete');
            // ->middleware(['permission:EliminarLugar']);
    });

    // Cliente - Lugar
    Route::group(["prefix" => "lugares"],function(){
        Route::get('/{cliente_id}', [Parametrizacion\LugarController::class,'index'])->name('lugares.index');
        Route::post('/{cliente_id}', [Parametrizacion\LugarController::class,'store'])->name('lugares.store');
            // ->middleware(['permission:CrearLugar']);
        Route::get('/{cliente_id}/{id}', [Parametrizacion\LugarController::class,'show'])->name('lugares.show');
            // ->middleware(['permission:ListarLugar']);
        Route::put('/{cliente_id}/{id}', [Parametrizacion\LugarController::class,'update'])->name('lugares.update');
            // ->middleware(['permission:ModificarLugar']);
        Route::delete('/{id}', [Parametrizacion\LugarController::class,'destroy'])->name('lugares.delete');
            // ->middleware(['permission:EliminarLugar']);
    });

    // Tipos Eventos
    Route::group(["prefix" => "tipos-eventos"],function(){
        Route::get('/', [Parametrizacion\TipoEventoController::class,'index'])->name('tipos-eventos.index');
        Route::post('/', [Parametrizacion\TipoEventoController::class,'store'])->name('tipos-eventos.store');
            // ->middleware(['permission:CrearAplicacion']);
        Route::get('/{id}', [Parametrizacion\TipoEventoController::class,'show'])->name('tipos-eventos.show');
            // ->middleware(['permission:ListarAplicacion']);
        Route::put('/{id}', [Parametrizacion\TipoEventoController::class,'update'])->name('tipos-eventos.update');
            // ->middleware(['permission:ModificarAplicacion']);
        Route::delete('/{id}', [Parametrizacion\TipoEventoController::class,'destroy'])->name('tipos-eventos.delete');
            // ->middleware(['permission:EliminarAplicacion']);
    });

    // Kits
    Route::group(["prefix" => "kits"],function(){
        Route::get('/', [Parametrizacion\KitController::class,'index'])->name('kits.index');
        Route::post('/', [Parametrizacion\KitController::class,'store'])->name('kits.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\KitController::class,'show'])->name('kits.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\KitController::class,'update'])->name('kits.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\KitController::class,'destroy'])->name('kits.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Kits Productos
    Route::group(["prefix" => "kits-productos"],function(){
        Route::get('/{kit_id}', [Parametrizacion\KitProductoController::class,'index'])->name('kits-productos.index');
        Route::post('/{kit_id}', [Parametrizacion\KitProductoController::class,'store'])->name('kits-productos.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{kit_id}/{id}', [Parametrizacion\KitProductoController::class,'show'])->name('kits-productos.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{kit_id}/{id}', [Parametrizacion\KitProductoController::class,'update'])->name('kits-productos.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\KitProductoController::class,'destroy'])->name('kits-productos.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Cliente - Alerta
    Route::group(["prefix" => "clientes-alertas"],function(){
        Route::get('/{cliente_id}', [Parametrizacion\ClienteAlertaController::class,'index'])->name('clientes-alertas.index');
        Route::post('/{cliente_id}', [Parametrizacion\ClienteAlertaController::class,'store'])->name('clientes-alertas.store');
            // ->middleware(['permission:CrearClienteAlerta']);
        Route::get('/{cliente_id}/{id}', [Parametrizacion\ClienteAlertaController::class,'show'])->name('clientes-alertas.show');
            // ->middleware(['permission:ListarClienteAlerta']);
        Route::put('/{cliente_id}/{id}', [Parametrizacion\ClienteAlertaController::class,'update'])->name('clientes-alertas.update');
            // ->middleware(['permission:ModificarClienteAlerta']);
        Route::delete('/{id}', [Parametrizacion\ClienteAlertaController::class,'destroy'])->name('clientes-alertas.delete');
            // ->middleware(['permission:EliminarClienteAlerta']);
    });

    // Cliente - Vehiculos
    Route::group(["prefix" => "clientes-vehiculos"],function(){
        Route::get('/{cliente_id}', [Parametrizacion\ClienteVehiculoController::class,'index'])->name('clientes-vehiculos.index');
        Route::post('/{cliente_id}', [Parametrizacion\ClienteVehiculoController::class,'store'])->name('clientes-vehiculos.store');
            // ->middleware(['permission:CrearClienteVehiculo']);
        Route::get('/{cliente_id}/{id}', [Parametrizacion\ClienteVehiculoController::class,'show'])->name('clientes-vehiculos.show');
            // ->middleware(['permission:ListarClienteVehiculo']);
        Route::put('/{cliente_id}/{id}', [Parametrizacion\ClienteVehiculoController::class,'update'])->name('clientes-vehiculos.update');
            // ->middleware(['permission:ModificarClienteVehiculo']);
        Route::delete('/{id}', [Parametrizacion\ClienteVehiculoController::class,'destroy'])->name('clientes-vehiculos.delete');
            // ->middleware(['permission:EliminarClienteVehiculo']);
    });

    // Cliente - Conductores
    Route::group(["prefix" => "clientes-conductores"],function(){
        Route::get('/{cliente_id}', [Parametrizacion\ClienteConductorController::class,'index'])->name('clientes-conductores.index');
        Route::post('/{cliente_id}', [Parametrizacion\ClienteConductorController::class,'store'])->name('clientes-conductores.store');
            // ->middleware(['permission:CrearClienteConductor']);
        Route::get('/{cliente_id}/{id}', [Parametrizacion\ClienteConductorController::class,'show'])->name('clientes-conductores.show');
            // ->middleware(['permission:ListarClienteConductor']);
        Route::put('/{cliente_id}/{id}', [Parametrizacion\ClienteConductorController::class,'update'])->name('clientes-conductores.update');
            // ->middleware(['permission:ModificarClienteConductor']);
        Route::delete('/{id}', [Parametrizacion\ClienteConductorController::class,'destroy'])->name('clientes-conductores.delete');
            // ->middleware(['permission:EliminarClienteConductor']);
    });

    // Cliente - Conductores
    Route::group(["prefix" => "clientes-inspectores"],function(){
        Route::get('/{cliente_id}', [Parametrizacion\ClienteInspectorController::class,'index'])->name('clientes-inspectores.index');
        Route::post('/{cliente_id}', [Parametrizacion\ClienteInspectorController::class,'store'])->name('clientes-inspectores.store');
            // ->middleware(['permission:CrearClienteConductor']);
        Route::get('/{cliente_id}/{id}', [Parametrizacion\ClienteInspectorController::class,'show'])->name('clientes-inspectores.show');
            // ->middleware(['permission:ListarClienteConductor']);
        Route::put('/{cliente_id}/{id}', [Parametrizacion\ClienteInspectorController::class,'update'])->name('clientes-inspectores.update');
            // ->middleware(['permission:ModificarClienteConductor']);
        Route::delete('/{id}', [Parametrizacion\ClienteInspectorController::class,'destroy'])->name('clientes-inspectores.delete');
            // ->middleware(['permission:EliminarClienteConductor']);
    });
    
    // Clientes - Empresas Transporte 
    Route::group(["prefix" => "clientes-empresas-transporte"],function(){
        Route::get('/{cliente_id}', [Parametrizacion\ClienteEmpresasTransporteController::class,'index'])->name('clientes-empresas-transporte.index');
        Route::post('/{cliente_id}', [Parametrizacion\ClienteEmpresasTransporteController::class,'store'])->name('clientes-empresas-transporte.store');
            // ->middleware(['permission:CrearClienteConductor']);
        Route::get('/{cliente_id}/{id}', [Parametrizacion\ClienteEmpresasTransporteController::class,'show'])->name('clientes-empresas-transporte.show');
            // ->middleware(['permission:ListarClienteConductor']);
        Route::put('/{cliente_id}/{id}', [Parametrizacion\ClienteEmpresasTransporteController::class,'update'])->name('clientes-empresas-transporte.update');
            // ->middleware(['permission:ModificarClienteConductor']);
        Route::delete('/{id}', [Parametrizacion\ClienteEmpresasTransporteController::class,'destroy'])->name('clientes-empresas-transporte.delete');
            // ->middleware(['permission:EliminarClienteConductor']);
    });

    // Tipos Contenedores
    Route::group(["prefix" => "tipos-contenedor"],function(){
        Route::get('/', [Parametrizacion\TipoContenedorController::class,'index'])->name('tipos-contenedor.index');
        Route::post('/', [Parametrizacion\TipoContenedorController::class,'store'])->name('tipos-contenedor.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\TipoContenedorController::class,'show'])->name('tipos-contenedor.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\TipoContenedorController::class,'update'])->name('tipos-contenedor.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\TipoContenedorController::class,'destroy'])->name('tipos-contenedor.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Contenedores
    Route::group(["prefix" => "contenedores"],function(){
        Route::get('/', [Parametrizacion\ContenedorController::class,'index'])->name('contenedores.index');
        Route::post('/', [Parametrizacion\ContenedorController::class,'store'])->name('contenedores.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\ContenedorController::class,'show'])->name('contenedores.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\ContenedorController::class,'update'])->name('contenedores.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\ContenedorController::class,'destroy'])->name('contenedores.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Lugares Usuarios
    Route::group(["prefix" => "lugares-usuarios"],function(){
        Route::get('/', [Parametrizacion\LugarUsuarioController::class,'index'])->name('lugares-usuarios.index');
        Route::post('/', [Parametrizacion\LugarUsuarioController::class,'store'])->name('lugares-usuarios.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\LugarUsuarioController::class,'show'])->name('lugares-usuarios.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\LugarUsuarioController::class,'update'])->name('lugares-usuarios.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\LugarUsuarioController::class,'destroy'])->name('lugares-usuarios.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Inventario Minimo
    Route::group(["prefix" => "inventario-minimo"],function(){
        Route::get('/', [Parametrizacion\InventarioMinimoController::class,'index'])->name('inventario-minimo.index');
        Route::post('/', [Parametrizacion\InventarioMinimoController::class,'store'])->name('inventario-minimo.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\InventarioMinimoController::class,'show'])->name('inventario-minimo.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\InventarioMinimoController::class,'update'])->name('inventario-minimo.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\InventarioMinimoController::class,'destroy'])->name('inventario-minimo.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Tipos de Chequeos
    Route::group(["prefix" => "tipos-chequeos"],function(){
        Route::get('/', [Parametrizacion\TipoChequeoController::class,'index'])->name('tipos-chequeos.index');
        Route::post('/', [Parametrizacion\TipoChequeoController::class,'store'])->name('tipos-chequeos.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\TipoChequeoController::class,'show'])->name('tipos-chequeos.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\TipoChequeoController::class,'update'])->name('tipos-chequeos.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\TipoChequeoController::class,'destroy'])->name('tipos-chequeos.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Unidades de Carga o Transporte
    Route::group(["prefix" => "unidades-carga-transporte"],function(){
        Route::get('/', [Parametrizacion\UnidadCargaTransporteController::class,'index'])->name('unidades-carga-transporte.index');
        Route::post('/', [Parametrizacion\UnidadCargaTransporteController::class,'store'])->name('unidades-carga-transporte.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\UnidadCargaTransporteController::class,'show'])->name('unidades-carga-transporte.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\UnidadCargaTransporteController::class,'update'])->name('unidades-carga-transporte.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\UnidadCargaTransporteController::class,'destroy'])->name('unidades-carga-transporte.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Listas Chequeo
    Route::group(["prefix" => "listas-chequeos"],function(){
        Route::get('/{unidad_carga_id}', [Parametrizacion\TipoListaChequeoController::class,'index'])->name('listas-chequeos.index');
        Route::post('/{unidad_carga_id}', [Parametrizacion\TipoListaChequeoController::class,'store'])->name('listas-chequeos.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{unidad_carga_id}/{id}', [Parametrizacion\TipoListaChequeoController::class,'show'])->name('listas-chequeos.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\TipoListaChequeoController::class,'update'])->name('listas-chequeos.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\TipoListaChequeoController::class,'destroy'])->name('listas-chequeos.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Tipos de Chequeos Por Lista
    Route::group(["prefix" => "tipos-chequeos-por-lista"],function(){
        Route::get('/{lista_chequeo_id}', [Parametrizacion\TipoChequeoPorListaController::class,'index'])->name('tipos-chequeos-por-lista.index');
        Route::post('/{lista_chequeo_id}', [Parametrizacion\TipoChequeoPorListaController::class,'store'])->name('tipos-chequeos-por-lista.store');
            // ->middleware(['permission:CrearAccionLugar']);
    });

    // Tipos de Chequeos
    Route::group(["prefix" => "clases-inspeccion"],function(){
        Route::get('/', [Parametrizacion\ClaseInspeccionController::class,'index'])->name('clases-inspeccion.index');
        Route::post('/', [Parametrizacion\ClaseInspeccionController::class,'store'])->name('clases-inspeccion.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\ClaseInspeccionController::class,'show'])->name('clases-inspeccion.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\ClaseInspeccionController::class,'update'])->name('clases-inspeccion.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\ClaseInspeccionController::class,'destroy'])->name('clases-inspeccion.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Cliente - Conductores
    Route::group(["prefix" => "clases-inspeccion-lista-fotos"],function(){
        Route::get('/{clase_inspeccion_id}', [Parametrizacion\ClaseInspeccionListaFotosController::class,'index'])->name('clases-inspeccion-lista-fotos.index');
        Route::post('/{clase_inspeccion_id}', [Parametrizacion\ClaseInspeccionListaFotosController::class,'store'])->name('clases-inspeccion-lista-fotos.store');
            // ->middleware(['permission:CrearClienteConductor']);
        Route::get('/{clase_inspeccion_id}/{id}', [Parametrizacion\ClaseInspeccionListaFotosController::class,'show'])->name('clases-inspeccion-lista-fotos.show');
            // ->middleware(['permission:ListarClienteConductor']);
        Route::put('/{clase_inspeccion_id}/{id}', [Parametrizacion\ClaseInspeccionListaFotosController::class,'update'])->name('clases-inspeccion-lista-fotos.update');
            // ->middleware(['permission:ModificarClienteConductor']);
        Route::delete('/{id}', [Parametrizacion\ClaseInspeccionListaFotosController::class,'destroy'])->name('clases-inspeccion-lista-fotos.delete');
            // ->middleware(['permission:EliminarClienteConductor']);
    });

    // Datos Adicionales
    Route::group(["prefix" => "datos-adicionales"],function(){
        Route::get('/', [Parametrizacion\DatoAdicionalController::class,'index'])->name('datos-adicionales.index');
        Route::post('/', [Parametrizacion\DatoAdicionalController::class,'store'])->name('datos-adicionales.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\DatoAdicionalController::class,'show'])->name('datos-adicionales.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\DatoAdicionalController::class,'update'])->name('datos-adicionales.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\DatoAdicionalController::class,'destroy'])->name('datos-adicionales.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Formatos Inspeccions
    Route::group(["prefix" => "formatos-inspeccion"],function(){
        Route::get('/', [Parametrizacion\FormatoInspeccionController::class,'index'])->name('formatos-inspeccion.index');
        Route::post('/', [Parametrizacion\FormatoInspeccionController::class,'store'])->name('formatos-inspeccion.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\FormatoInspeccionController::class,'show'])->name('formatos-inspeccion.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\FormatoInspeccionController::class,'update'])->name('formatos-inspeccion.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\FormatoInspeccionController::class,'destroy'])->name('formatos-inspeccion.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Formatos Inspeccions Unidades
    Route::group(["prefix" => "formatos-inspeccion-unidades"],function(){
        Route::get('/', [Parametrizacion\FormatoInspeccionUnidadController::class,'index'])->name('formatos-inspeccion-unidades.index');
        Route::post('/', [Parametrizacion\FormatoInspeccionUnidadController::class,'store'])->name('formatos-inspeccion-unidades.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Parametrizacion\FormatoInspeccionUnidadController::class,'show'])->name('formatos-inspeccion-unidades.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Parametrizacion\FormatoInspeccionUnidadController::class,'update'])->name('formatos-inspeccion-unidades.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Parametrizacion\FormatoInspeccionUnidadController::class,'destroy'])->name('formatos-inspeccion-unidades.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // ---------------------- Pedidos -------------------------- //
    // Pedidos
    Route::group(["prefix" => "pedidos"],function(){
        Route::get('/', [Pedidos\PedidoController::class,'index'])->name('pedidos.index');
        Route::post('/', [Pedidos\PedidoController::class,'store'])->name('pedidos.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Pedidos\PedidoController::class,'show'])->name('pedidos.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/confirmar/{id}', [Pedidos\PedidoController::class,'confirmar'])->name('pedidos.confirmar');
        Route::put('/{id}', [Pedidos\PedidoController::class,'update'])->name('pedidos.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Pedidos\PedidoController::class,'destroy'])->name('pedidos.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Pedidos Detalle
    Route::group(["prefix" => "pedidos-detalle"],function(){
        Route::get('/kit/{pedido_id}/{kit_id}', [Pedidos\PedidoDetalleController::class,'indexKit'])->name('pedidos-detalle.indexKit');
        Route::get('/{pedido_id}', [Pedidos\PedidoDetalleController::class,'index'])->name('pedidos-detalle.index');
        Route::post('/{pedido_id}', [Pedidos\PedidoDetalleController::class,'store'])->name('pedidos-detalle.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{pedido_id}/{id}', [Pedidos\PedidoDetalleController::class,'show'])->name('pedidos-detalle.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{pedido_id}/{id}', [Pedidos\PedidoDetalleController::class,'update'])->name('pedidos-detalle.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Pedidos\PedidoDetalleController::class,'destroy'])->name('pedidos-detalle.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Pedidos S3
    Route::group(["prefix" => "pedidos-s3"],function(){
        Route::get('/', [Pedidos\PedidoS3Controller::class,'index'])->name('pedidos-s3.index');
    });

    // Sellos
    Route::group(["prefix" => "sellos"],function(){
        Route::get('/consulta-inventario', [Pedidos\SelloController::class,'consultar'])->name('sellos.consultar');
        Route::get('/{numero_pedido}', [Pedidos\SelloController::class,'index'])->name('sellos.index');
        Route::post('/importar', [Pedidos\SelloController::class,'importar'])->name('sellos.importar');
        Route::get('/exportar-inventario', [Pedidos\SelloController::class,'exportar'])->name('sellos.exportar');
        Route::post('/', [Pedidos\SelloController::class,'store'])->name('sellos.store');
        Route::delete('/{id}', [Pedidos\SelloController::class,'destroy'])->name('sellos.delete');
    });

    // Inventario
    Route::group(["prefix" => "inventario"],function(){
        Route::get('/', [Pedidos\SelloController::class,'consultarStockMinimo'])->name('sellos.consultarStockMinimo');
        Route::get('/lugar', [Pedidos\SelloController::class,'consultarStockMinimoPorLugar'])->name('sellos.consultarStockMinimoPorLugar');
    });

    // ---------------------- Remisiones -------------------------- //
    
    // Terceros S3
    Route::group(["prefix" => "terceros-s3"],function(){
        Route::get('/', [Parametrizacion\TerceroController::class,'index'])->name('terceros-s3.index');
    });

    // Remisiones
    Route::group(["prefix" => "remisiones"],function(){
        Route::get('/', [Remisiones\RemisionController::class,'index'])->name('remisiones.index');
        Route::post('/', [Remisiones\RemisionController::class,'store'])->name('remisiones.store');
        Route::post('/{id}', [Remisiones\RemisionController::class,'confirmarORechazar'])->name('remisiones.confirmarORechazar');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Remisiones\RemisionController::class,'show'])->name('remisiones.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/anular/{id}', [Remisiones\RemisionController::class,'destroy'])->name('remisiones.delete');
        Route::put('/{id}', [Remisiones\RemisionController::class,'update'])->name('remisiones.update');
            // ->middleware(['permission:ModificarAccionLugar']);
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // RemisionesDetalle
    Route::group(["prefix" => "remisiones-detalle"],function(){
        Route::get('/', [Remisiones\RemisionDetalleController::class,'index'])->name('remisiones-detalle.index');
        Route::get('/leer', [Remisiones\RemisionDetalleController::class,'read'])->name('remisiones-detalle.read');
        Route::post('/', [Remisiones\RemisionDetalleController::class,'store'])->name('remisiones-detalle.store');
        Route::post('/todos', [Remisiones\RemisionDetalleController::class,'storeAll'])->name('remisiones-detalle.storeAll');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Remisiones\RemisionDetalleController::class,'show'])->name('remisiones-detalle.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Remisiones\RemisionDetalleController::class,'update'])->name('remisiones-detalle.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Remisiones\RemisionDetalleController::class,'destroy'])->name('remisiones-detalle.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // ---------------------- Contenedores -------------------------- //

    // Sellos Bitacora
    Route::group(["prefix" => "sellos-bitacora"],function(){
        Route::get('/', [SelloBitacoraController::class,'index'])->name('sellos-bitacora.index');
        Route::get('/consulta-totales', [SelloBitacoraController::class,'indexConsulta'])->name('sellos-bitacora.indexConsulta');
        Route::get('/{id}', [SelloBitacoraController::class,'show'])->name('sellos-bitacora.show');
    });
    
    // Instalacion
    Route::group(["prefix" => "instalar-sellos"],function(){
        Route::get('/', [Pedidos\SelloController::class,'indexInstalacion'])->name('instalar-sellos.indexInstalacion');
        Route::post('/leer', [Pedidos\SelloController::class,'leerSelloParaInstalar'])->name('instalar-sellos.leerSelloParaInstalar');
        Route::post('/instalar', [Pedidos\SelloController::class,'instalar'])->name('instalar-sellos.instalar');
    });

    // Actualizacion Estado
    Route::group(["prefix" => "actualizar-estado"],function(){
        Route::get('/', [Pedidos\SelloController::class,'indexActualizarEstado'])->name('actualizar-estado.indexActualizarEstado');
        Route::post('/', [Pedidos\SelloController::class,'actualizarEstado'])->name('actualizar-estado.actualizarEstado');
    });

    // ---------------------- Operaciones -------------------------- //

    // Operaciones de Embarque
    Route::group(["prefix" => "operaciones-embarque"],function(){
        Route::get('/', [Operaciones\OperacionEmbarqueController::class,'index'])->name('operaciones-embarque.index');
        Route::post('/', [Operaciones\OperacionEmbarqueController::class,'store'])->name('operaciones-embarque.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{id}', [Operaciones\OperacionEmbarqueController::class,'show'])->name('operaciones-embarque.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Operaciones\OperacionEmbarqueController::class,'update'])->name('operaciones-embarque.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Operaciones\OperacionEmbarqueController::class,'destroy'])->name('operaciones-embarque.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });

    // Operaciones de Embarque Contenedor
    Route::group(["prefix" => "operaciones-embarque-contenedores"],function(){
        Route::get('/{operacion_embarque_id}', [Operaciones\OperacionEmbarqueContenedorController::class,'index'])->name('operaciones-embarque-contenedores.index');
        Route::post('/importar', [Operaciones\OperacionEmbarqueContenedorController::class,'importar'])->name('operaciones-embarque-contenedores.importar');
        Route::post('/{operacion_embarque_id}', [Operaciones\OperacionEmbarqueContenedorController::class,'store'])->name('operaciones-embarque-contenedores.store');
            // ->middleware(['permission:CrearAccionLugar']);
        Route::get('/{operacion_embarque_id}/{id}', [Operaciones\OperacionEmbarqueContenedorController::class,'show'])->name('operaciones-embarque-contenedores.show');
            // ->middleware(['permission:ListarAccionLugar']);
        Route::put('/{id}', [Operaciones\OperacionEmbarqueContenedorController::class,'update'])->name('operaciones-embarque-contenedores.update');
            // ->middleware(['permission:ModificarAccionLugar']);
        Route::delete('/{id}', [Operaciones\OperacionEmbarqueContenedorController::class,'destroy'])->name('operaciones-embarque-contenedores.delete');
            // ->middleware(['permission:EliminarAccionLugar']);
    });
});
