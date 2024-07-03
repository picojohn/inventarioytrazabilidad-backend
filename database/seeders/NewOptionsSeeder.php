<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NewOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Solicitudes de Acceso',
            'modulo_id'=> 1,
            'posicion'=> 2,
            'icono_menu'=> 'lock_open',
            'url'=> '/solicitudes-acceso',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarSolicitudAcceso',
            'guard_name'=> 'api',
            'option_id'=> 31,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarSolicitudAcceso',
            'guard_name'=> 'api',
            'option_id'=> 31,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarSolicitudAcceso',
            'guard_name'=> 'api',
            'option_id'=> 31,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 116,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 117,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 118,
            'role_id'=> 1,
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearInspectorCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'CrearIns',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarInspectorCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'ModificarIns',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarInspectorCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'EliminarIns',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarInspectorCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'ListarIns',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearEmpresaTransporteCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'CrearEmpTra',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarEmpresaTransporteCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'ModificarEmpTra',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarEmpresaTransporteCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'EliminarEmpTra',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarEmpresaTransporteCliente',
            'guard_name'=> 'api',
            'option_id'=> 8,
            'title'=> 'ListarEmpTra',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 119,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 120,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 121,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 122,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 123,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 124,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 125,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 126,
            'role_id'=> 1,
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ExportarProductoCliente',
            'guard_name'=> 'api',
            'option_id'=> 10,
            'title'=> 'Exportar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 127,
            'role_id'=> 1,
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Tipos de Chequeos',
            'modulo_id'=> 2,
            'posicion'=> 55,
            'icono_menu'=> 'playlist_add_check',
            'url'=> '/tipos-chequeos',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Unidades de Carga/Transporte',
            'modulo_id'=> 2,
            'posicion'=> 60,
            'icono_menu'=> 'local_shipping',
            'url'=> '/unidades-carga-transporte',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Clases de Inspección',
            'modulo_id'=> 2,
            'posicion'=> 65,
            'icono_menu'=> 'playlist_add_check',
            'url'=> '/clases-inspeccion',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Datos Adicionales',
            'modulo_id'=> 2,
            'posicion'=> 70,
            'icono_menu'=> 'playlist_add',
            'url'=> '/datos-adicionales',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Formatos Inspección',
            'modulo_id'=> 2,
            'posicion'=> 80,
            'icono_menu'=> 'list_all',
            'url'=> '/formatos-inspeccion',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearTipoChequeo',
            'guard_name'=> 'api',
            'option_id'=> 32,
            'title'=> 'Crear',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarTipoChequeo',
            'guard_name'=> 'api',
            'option_id'=> 32,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarTipoChequeo',
            'guard_name'=> 'api',
            'option_id'=> 32,
            'title'=> 'Modificar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarTipoChequeo',
            'guard_name'=> 'api',
            'option_id'=> 32,
            'title'=> 'Eliminar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearUnidadCargaTransporte',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'Crear',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarUnidadCargaTransporte',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarUnidadCargaTransporte',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'Modificar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarUnidadCargaTransporte',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'Eliminar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearListaChequeo',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'CrearLisChe',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarListaChequeo',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'ListarLisChe',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarListaChequeo',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'ModificarLisChe',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarListaChequeo',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'EliminarLisChe',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarTipoChequeoLista',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'ListarTipChe',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarTipoChequeoLista',
            'guard_name'=> 'api',
            'option_id'=> 33,
            'title'=> 'ModificarTipChe',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearClaseInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 34,
            'title'=> 'Crear',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarClaseInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 34,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarClaseInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 34,
            'title'=> 'Modificar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarClaseInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 34,
            'title'=> 'Eliminar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearDatoAdicional',
            'guard_name'=> 'api',
            'option_id'=> 35,
            'title'=> 'Crear',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarDatoAdicional',
            'guard_name'=> 'api',
            'option_id'=> 35,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarDatoAdicional',
            'guard_name'=> 'api',
            'option_id'=> 35,
            'title'=> 'Modificar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarDatoAdicional',
            'guard_name'=> 'api',
            'option_id'=> 35,
            'title'=> 'Eliminar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearFormatoInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 36,
            'title'=> 'Crear',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ListarFormatoInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 36,
            'title'=> 'Listar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarFormatoInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 36,
            'title'=> 'Modificar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'EliminarFormatoInspeccion',
            'guard_name'=> 'api',
            'option_id'=> 36,
            'title'=> 'Eliminar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 128,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 129,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 130,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 131,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 132,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 133,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 134,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 135,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 136,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 137,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 138,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 139,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 140,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 141,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 142,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 143,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 144,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 145,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 146,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 147,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 148,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 149,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 150,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 151,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 152,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 153,
            'role_id'=> 1,
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ConfirmarPedido',
            'guard_name'=> 'api',
            'option_id'=> 20,
            'title'=> 'Confirmar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 154,
            'role_id'=> 1,
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Consulta Inventario - Stock Mínimo',
            'modulo_id'=> 4,
            'posicion'=> 20,
            'icono_menu'=> 'search',
            'url'=> '/inventario',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ConsultarStockMinimo',
            'guard_name'=> 'api',
            'option_id'=> 37,
            'title'=> 'Consultar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 155,
            'role_id'=> 1,
        ]);
    }
}
