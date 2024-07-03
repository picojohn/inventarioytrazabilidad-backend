<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ContenedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('modulos')->insert([
            'nombre'=> 'Contenedores',
            'aplicacion_id'=> 1,
            'estado'=> true,
            'icono_menu'=> 'list_all',
            'posicion'=> 25,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Consulta Trazabilidad',
            'modulo_id'=> 6,
            'posicion'=> 5,
            'icono_menu'=> 'trending_up',
            'url'=> '/sellos-bitacora',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Instalación',
            'modulo_id'=> 6,
            'posicion'=> 10,
            'icono_menu'=> 'scatter_plot',
            'url'=> '/instalar',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Actualización Estado',
            'modulo_id'=> 6,
            'posicion'=> 15,
            'icono_menu'=> 'track_changes',
            'url'=> '/actualizar-estado',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('opciones_del_sistema')->insert([
            'nombre'=> 'Consulta Totales',
            'modulo_id'=> 6,
            'posicion'=> 20,
            'icono_menu'=> 'bar_chart',
            'url'=> '/consulta-totales',
            'estado'=> true,
            'usuario_creacion_id' =>1,
            'usuario_creacion_nombre'=>'SuperUser',
            'usuario_modificacion_id' =>1,
            'usuario_modificacion_nombre' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ConsultarConsultaTrazabilidad',
            'guard_name'=> 'api',
            'option_id'=> 26,
            'title'=> 'Consultar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ExportarConsultaTrazabilidad',
            'guard_name'=> 'api',
            'option_id'=> 26,
            'title'=> 'Exportar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'CrearInstalacion',
            'guard_name'=> 'api',
            'option_id'=> 27,
            'title'=> 'Crear',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ModificarActualizacionEstado',
            'guard_name'=> 'api',
            'option_id'=> 28,
            'title'=> 'Modificar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('permissions')->insert([
            'name'=> 'ConsultarConsultaTotales',
            'guard_name'=> 'api',
            'option_id'=> 29,
            'title'=> 'Consultar',
            'user_creation_id' =>1,
            'user_creation_name'=>'SuperUser',
            'user_modification_id' =>1,
            'user_modification_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 107,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 108,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 109,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 110,
            'role_id'=> 1,
        ]);
        DB::table('role_has_permissions')->insert([
            'permission_id'=> 111,
            'role_id'=> 1,
        ]);
    }
}
