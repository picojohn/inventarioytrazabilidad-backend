<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Http\Response;
use App\Enum\AccionAuditoriaEnum;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Seguridad\InicioSesion;
use App\Models\Seguridad\AuditoriaTabla;
use Illuminate\Support\Facades\Password;
use App\Models\Seguridad\SolicitudAcceso;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\RequestException;
use App\Models\Parametrizacion\ParametroCorreo;
use App\Models\Parametrizacion\ParametroConstante;

class UserController extends Controller
{
    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:4',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = ['token' => $token];
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 422);
        }
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:18',
            'email' => 'required|string|unique:users',
            'password' => 'required|string|min:4',
        ]);
        if($validator->fails()){
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $request['password']=Hash::make($request['password']);
        $request['remember_token']=Str::random(10);
        $user = User::create($request->toArray());
        $token = $user->createToken('Proyectarte Password Grant Client')->accessToken;
        $response = ['token' => $token];
        return response($response, 200);
    }

    public function getToken(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            'latitud' => 'numeric|required',
            'longitud' => 'numeric|required',
        ]);

        if($validator->fails()){
            $errors = $validator->errors();
            return response([
                "messages" => $errors
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $http = new Client;
        $hostname = env("APP_URL");;
        
        try{
            $user = User::where('email',$request->username)->first();
            if(isset($user)){

                $response = $http->post($hostname.'/oauth/token', [
                    'form_params' => [
                        'client_id' => env("PASSWORD_CLIENT_ID"),
                        'client_secret' => env("PASSWORD_CLIENT_SECRET"),
                        'grant_type' => 'password',
                        'username' => $request->username,
                        'password' => $request->password
                    ]
                ]);
                
                if($response->getStatusCode() == Response::HTTP_OK){
                    $responseBody = json_decode((string) $response->getBody(), true);
                    InicioSesion::create([
                        'cliente_id' => $user->asociado()->id,
                        'usuario_id' => $user->usuario()->id,
                        'latitud' => $request->all()['latitud'],
                        'longitud' => $request->all()['longitud'],
                    ]);
                    if($request->movil){
                        $usuario = $user->usuario();
                        SolicitudAcceso::create([
                            'cliente_id' => $user->asociado()->id,
                            'usuario_id' => $usuario->id,
                            'fecha_solicitud' => Carbon::now(),
                            'usuario_creacion_id' => $usuario->id,
                            'usuario_creacion_nombre' => $usuario->nombre,
                            'usuario_modificacion_id' => $usuario->id,
                            'usuario_modificacion_nombre' => $usuario->nombre,
                        ]);
                    }
                    return response($responseBody,Response::HTTP_OK);
                } else {
                    return response([
                    
                        "messages" => ["La contraseña ingresada es inválidasss."]
                    ],Response::HTTP_UNAUTHORIZED);
                }
            }else{
                return response([
                    "messages" => ["El usuario con identificación " . $request->username . " no está registrado."]
                ],Response::HTTP_UNAUTHORIZED);
            }
        }catch (RequestException $e){
            return response([
                "data" => $e->getMessage(),
                "messages" => ["La contraseña ingresada es inválida."]
            ],Response::HTTP_UNAUTHORIZED);
        }catch (Exception $e){
            return response([
                "data" => $e->getMessage(),
                "messages" => $e->getMessage()
            ],Response::HTTP_UNAUTHORIZED);
        }
    }

    public function getSession(){
        try{
            // Constantes

            $user = Auth::user();
            $rol = $user->rol();
            $usuario = $user->usuario();
            $asociado = $user->asociado();
            

            $query = DB::table('modulos')
            ->join('opciones_del_sistema', 'opciones_del_sistema.modulo_id', '=', 'modulos.id')
            ->join('permissions', 'opciones_del_sistema.id', '=', 'permissions.option_id')
            ->join('role_has_permissions', function ($join) use($rol) {
                $join->on('role_has_permissions.permission_id', '=', 'permissions.id')
                    ->where('role_has_permissions.role_id', $rol->id);
            })
            ->select(
                'modulos.id',
                'modulos.nombre as modulo',
                'modulos.icono_menu',
                'modulos.posicion',
                'opciones_del_sistema.id AS id_opcion',
                'opciones_del_sistema.nombre AS opcion',
                'opciones_del_sistema.posicion AS posicion_opcion',
                'opciones_del_sistema.icono_menu AS icono_menu_opcion',
                'opciones_del_sistema.url',
                'opciones_del_sistema.url_ayuda',
                'permissions.id AS id_permiso',
                'permissions.name AS nombre_permiso',
                'permissions.title AS titulo_permiso',
                DB::raw('IF(role_has_permissions.permission_id IS NOT NULL, 1, 0) AS permitido')
            )
            ->where('modulos.estado','=',true)
            ->where('opciones_del_sistema.estado','=',true)
            ->orderBy('modulos.posicion', 'asc')
            ->orderBy('opciones_del_sistema.posicion', 'asc')
            ->get();
            $permisosPorModuloDto = [];
            $permisosPorModulo = $query->groupBy('modulo');
            foreach ($permisosPorModulo as $modulo => $permisosDelModulo){
                $permisosPorOpcionDto = [];
                $permisosPorOpcion = $permisosDelModulo->groupBy('opcion');
                foreach ($permisosPorOpcion as $opcion => $permisosOpcion){
                    $permisosDto = [];
                    foreach ($permisosOpcion as $permiso){
                        array_push($permisosDto, [
                            "nombre" => $permiso->nombre_permiso,
                            "titulo" => $permiso->titulo_permiso,
                            "permitido" => !!$permiso->permitido,
                            "id" => $permiso->id_permiso,
                        ]);
                    }
    
                    array_push($permisosPorOpcionDto, [
                        "id" => $permisosOpcion[0]->id_opcion,
                        "nombre" => $opcion,
                        "posicion" => $permisosOpcion[0]->posicion_opcion,
                        "icono_menu" => $permisosOpcion[0]->icono_menu_opcion,
                        "url" => $permisosOpcion[0]->url,
                        "url_ayuda" => $permisosOpcion[0]->url_ayuda,
                        "type"=>'item',
                        "permisos" => $permisosDto,
                    ]);
                }
    
                array_push($permisosPorModuloDto, [
                    "id" => $permisosDelModulo[0]->id,
                    "nombre" => $modulo,
                    "posicion" => $permisosDelModulo[0]->posicion,
                    "icono_menu" => $permisosDelModulo[0]->icono_menu,
                    "type"=>'collapse',
                    "opciones" => $permisosPorOpcionDto,
                ]);
            }
    
            return response([
                'usuario' => [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'correo_electronico' => $usuario->correo_electronico,
                    'identificacion_usuario' => $usuario->identificacion_usuario,
                    'asociado' => [
                        'id' => $asociado->id,
                        'nombre' => $asociado->nombre, 
                    ],
                    'rol' => [
                        'id'=>$rol->id,
                        'nombre' => $rol->name,
                        'tipo' => $rol->type,
                    ],
                    'permisos' =>$permisosPorModuloDto,
                ]
            ], Response::HTTP_OK);
        }catch (Exception $e){
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Revoke user tokens
     */
    public function logout(){
        try{
            $user = Auth::user();
            $userTokens = $user->tokens;
            foreach($userTokens as $token) {
                $token->revoke();
            }
            return response(null, Response::HTTP_OK);
        }catch (Exception $e){
            return response([
                "messages" => "Ocurrió un error al intentar cerrar la sesión."
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function forgotPassword(Request $request){
        $datos = $request->all();
        $validator = Validator::make($datos, [
            'email' => 'required|exists:users,email'
        ],
        ['exists'=>'El usuario ' .$request->email .' no existe.']);
    
        if($validator->fails()) {
            return response(
                get_response_body(format_messages_validator($validator))
                , Response::HTTP_BAD_REQUEST
            );
        }
    
        $user = User::where('email','=',$request->email)->limit(1)->get()[0];
        $usuario = $user->usuario();
    
        $token = Password::getRepository()->create($user);
        
        if(!isset($token)){
            return response([
                "mensajes" => ['Problema con servidor de correos']
            ], Response::HTTP_BAD_REQUEST);
        }

        $parametros = ParametroConstante::cargarParametros();
        
        if (!isset($parametros['ID_CORREO_CAMBIO_CLAVE'])){
            return response(
                ['mensajes'=>['El parámetro ID_CORREO_CAMBIO_CLAVE no existe']]
                , Response::HTTP_BAD_REQUEST
            );
        }

        $parametroCorreo = ParametroCorreo::find($parametros['ID_CORREO_CAMBIO_CLAVE']);
        
        if(!isset($parametroCorreo)){
            return response(
                ['mensajes'=>['El parámetro ID_CORREO_CAMBIO_CLAVE es inválido']]
                , Response::HTTP_BAD_REQUEST
            );
        }

        $parametroCorreo->texto = str_replace('&amp;1', env('APP_FRONT_URL'),$parametroCorreo->texto);
        $parametroCorreo->texto = str_replace('&amp;2',  env('APP_FRONT_URL') . '/reset-password/' . $token,$parametroCorreo->texto);
    
        Mail::send('mail.reset-password',
            ['texto' => $parametroCorreo->texto],
             function (Message $message) use ($user,$usuario,$parametroCorreo) {
            $message->subject($parametroCorreo->asunto);
            $message->to($usuario->correo_electronico);
        });
        
        return response([
            "mensajes" => ["El email ha sido enviado"]
        ],Response::HTTP_OK);
    }

    public function resetPassword(Request $request){
        $datos = $request->all();
        $validator = Validator::make($datos, [
            'token' => 'required',
            'email' => 'required|exists:users,email',
            'password' => 'required|confirmed',
        ],
        ['email.exists'=>'El usuario no existe.',
        'token.required'=>'El token es inválido.',
        ]);

        if($validator->fails()) {
            return response(
                get_response_body(format_messages_validator($validator))
                , Response::HTTP_BAD_REQUEST
            );
        }
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET){
            $usuario = Usuario::findBy($datos['email']);
            // Guardar auditoria
            $auditoriaDto = [
                'id_recurso' => $usuario->id,
                'nombre_recurso' => Usuario::class,
                'descripcion_recurso' => $usuario->nombre,
                'accion' => AccionAuditoriaEnum::CAMBIO_CONTRASENA,
                'recurso_original' => $usuario->toJson(),
                'recurso_resultante' =>$usuario->toJson(),
                'responsable_id' => $usuario->id,
                'responsable_nombre' => $usuario->nombre,
            ];

            $auditoria = AuditoriaTabla::create($auditoriaDto);
            if(!isset($auditoria)){
                throw new Exception(
                    "Ocurrió un error al intentar guardar la auditoria del recurso.",
                    $auditoria
                );
            }
            return response([
                "mensajes" => ["La contraseña ha sido actualizada"]
            ],Response::HTTP_OK);
        } elseif( $status === Password::INVALID_USER) {
            return response([
                "mensajes" => ['Usuario inválido']
            ], Response::HTTP_BAD_REQUEST);
        } elseif( $status === Password::INVALID_TOKEN) {
            return response([
                "mensajes" => ['Token inválido']
            ], Response::HTTP_BAD_REQUEST);
        } else {
            return response([
                "mensajes" => ['Hubo un error']
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
