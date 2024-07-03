<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StartupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name'=> 'SuperUser',
            'email'=> '123456789',
            'password'=> Hash::make('1234'),
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('clientes')->insert([
            'nombre'=> 'Sec Sel',
            'indicativo_lectura_sellos_externos'=> 'S',
            'indicativo_instalacion_contenedor'=> 'S',
            'indicativo_contenedor_exclusivo'=> 'S',
            'indicativo_operaciones_embarque'=> 'S',
            'indicativo_instalacion_automatica'=> 'S',
            'indicativo_registro_lugar_instalacion'=> 'S',
            'indicativo_registro_zona_instalacion'=> 'S',
            'indicativo_asignacion_serial_automatica'=> 'S',
            'indicativo_documento_referencia'=> 'S',
            'asociado_id'=> 2,
            'usuario_creacion_id'=> 1,
            'usuario_creacion_nombre'=> 'SuperUser',
            'usuario_modificacion_id'=>1,
            'usuario_modificacion_nombre'=> 'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('usuarios')->insert([
            'user_id'=>1,
            'identificacion_usuario'=> '123456789',
            'nombre'=> 'SuperUser',
            'asociado_id'=> 1,
            'correo_electronico'=> 'correo@correo.com',
            'usuario_creacion_id'=> 1,
            'usuario_creacion_nombre'=> 'SuperUser',
            'usuario_modificacion_id'=>1,
            'usuario_modificacion_nombre'=> 'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('roles')->insert([
            'name'=> 'SuperSu',
            'guard_name'=> 'api',
            'type'=> 'IN',
            'status'=>true,
            'creation_user_id' =>1,
            'creation_user_name'=>'SuperUser',
            'modification_user_id' =>1,
            'modification_user_name' =>'SuperUser',
            'created_at' =>Carbon::now(),
            'updated_at' =>Carbon::now(),
        ]);
        DB::table('model_has_roles')->insert([
            'role_id'=> 1,
            'model_type'=> 'App\Models\User',
            'model_id'=> 1,
        ]);
    }
}
