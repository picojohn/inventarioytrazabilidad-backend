<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kits_productos', function (Blueprint $table) {
            $table->dropForeign(['kit_id']);
            $table->dropForeign(['producto_id']);
            $table->foreign('kit_id')->references('id')->on('kits')->onDelete('cascade')->change();
            $table->foreign('producto_id')->references('id')->on('productos_clientes')->onDelete('cascade')->change();
        });
        Schema::table('inventario_minimo', function (Blueprint $table) {
            $table->dropForeign(['kit_id']);
            $table->foreign('kit_id')->references('id')->on('kits')->onDelete('cascade')->change();
            $table->dropForeign(['producto_cliente_id']);
            $table->foreign('producto_cliente_id')->references('id')->on('productos_clientes')->onDelete('cascade')->change();
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->dropForeign(['lugar_id']);
            $table->foreign('lugar_id')->references('id')->on('lugares')->onDelete('cascade')->change();
        });
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
        });
        Schema::table('pedidos_detalle', function (Blueprint $table) {
            $table->dropForeign(['kit_id']);
            $table->foreign('kit_id')->references('id')->on('kits')->onDelete('cascade')->change();
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')->references('id')->on('productos_clientes')->onDelete('cascade')->change();
            $table->dropForeign(['pedido_id']);
            $table->foreign('pedido_id')->references('id')->on('pedidos')->onDelete('cascade')->change();
        });
        Schema::table('sellos', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')->references('id')->on('productos_clientes')->onDelete('cascade')->change();
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->dropForeign(['producto_empaque_id']);
            $table->foreign('producto_empaque_id')->references('id')->on('sellos')->onDelete('cascade')->change();
            $table->dropForeign(['kit_id']);
            $table->foreign('kit_id')->references('id')->on('kits')->onDelete('cascade')->change();
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade')->change();
            $table->dropForeign(['lugar_id']);
            $table->foreign('lugar_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->dropForeign(['contenedor_id']);
            $table->foreign('contenedor_id')->references('id')->on('contenedores')->onDelete('cascade')->change();
            $table->dropForeign(['lugar_instalacion_id']);
            $table->foreign('lugar_instalacion_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->dropForeign(['zona_instalacion_id']);
            $table->foreign('zona_instalacion_id')->references('id')->on('zonas_contenedores')->onDelete('cascade')->change();
            $table->dropForeign(['ultimo_tipo_evento_id']);
            $table->foreign('ultimo_tipo_evento_id')->references('id')->on('tipos_eventos')->onDelete('cascade')->change();
        });
        Schema::table('remisiones_detalles', function (Blueprint $table) {
            $table->dropForeign(['kit_id']);
            $table->foreign('kit_id')->references('id')->on('kits')->onDelete('cascade')->change();
        });
        Schema::table('sellos_bitacora', function (Blueprint $table) {
            $table->dropForeign(['sello_id']);
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['producto_empaque_id']);
            $table->dropForeign(['kit_id']);
            $table->dropForeign(['tipo_evento_id']);
            $table->dropForeign(['lugar_origen_id']);
            $table->dropForeign(['lugar_destino_id']);
            $table->dropForeign(['usuario_destino_id']);
            $table->dropForeign(['contenedor_id']);
            $table->dropForeign(['lugar_instalacion_id']);
            $table->dropForeign(['zona_instalacion_id']);
            $table->foreign('sello_id')->references('id')->on('sellos')->onDelete('cascade')->change();
            $table->foreign('producto_id')->references('id')->on('productos_clientes')->onDelete('cascade')->change();
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->foreign('producto_empaque_id')->references('id')->on('sellos')->onDelete('cascade')->change();
            $table->foreign('kit_id')->references('id')->on('kits')->onDelete('cascade')->change();
            $table->foreign('tipo_evento_id')->references('id')->on('tipos_eventos')->onDelete('cascade')->change();
            $table->foreign('lugar_origen_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->foreign('lugar_destino_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->foreign('usuario_destino_id')->references('id')->on('usuarios')->onDelete('cascade')->change();
            $table->foreign('contenedor_id')->references('id')->on('contenedores')->onDelete('cascade')->change();
            $table->foreign('lugar_instalacion_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->foreign('zona_instalacion_id')->references('id')->on('zonas_contenedores')->onDelete('cascade')->change();
        });
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropForeign(['aplicacion_id']);
            $table->foreign('aplicacion_id')->references('id')->on('aplicaciones')->onDelete('cascade')->change();
        });
        Schema::table('opciones_del_sistema', function (Blueprint $table) {
            $table->dropForeign(['modulo_id']);
            $table->foreign('modulo_id')->references('id')->on('modulos')->onDelete('cascade')->change();
        });
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->foreign('option_id')->references('id')->on('opciones_del_sistema')->onDelete('cascade')->change();
        });
        Schema::table('lugares', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
        });
        Schema::table('clientes_alertas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['alerta_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->foreign('alerta_id')->references('id')->on('tipos_alertas')->onDelete('cascade')->change();
        });
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['asociado_id']);
            $table->foreign('asociado_id')->references('id')->on('clientes')->onDelete('cascade')->change();
        });
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
        });
        Schema::table('conductores', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
        });
        Schema::table('productos_clientes', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
        });
        Schema::table('kits', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
        });
        Schema::table('contenedores', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->dropForeign(['tipo_contenedor_id']);
            $table->foreign('tipo_contenedor_id')->references('id')->on('tipos_contenedores')->onDelete('cascade')->change();
        });
        Schema::table('lugares_usuarios', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->dropForeign(['lugar_id']);
            $table->foreign('lugar_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->dropForeign(['usuario_id']);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade')->change();
        });
        Schema::table('remisiones', function (Blueprint $table) {
            $table->dropForeign('remisiones_cliente_id_foreign');
            $table->dropForeign(['cliente_destino_id']);
            $table->dropForeign(['lugar_envio_id']);
            $table->dropForeign(['user_envio_id']);
            $table->dropForeign(['lugar_destino_id']);
            $table->dropForeign(['user_destino_id']);
            $table->foreign('cliente_envio_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->foreign('cliente_destino_id')->references('id')->on('clientes')->onDelete('cascade')->change();
            $table->foreign('lugar_envio_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->foreign('user_envio_id')->references('id')->on('usuarios')->onDelete('cascade')->change();
            $table->foreign('lugar_destino_id')->references('id')->on('lugares')->onDelete('cascade')->change();
            $table->foreign('user_destino_id')->references('id')->on('usuarios')->onDelete('cascade')->change();
        });
        Schema::table('remisiones_detalles', function (Blueprint $table) {
            $table->dropForeign(['sello_id']);
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['kit_id']);
            $table->foreign('sello_id')->references('id')->on('sellos')->onDelete('cascade')->change();
            $table->foreign('producto_id')->references('id')->on('productos_clientes')->onDelete('cascade')->change();
            $table->foreign('kit_id')->references('id')->on('kits')->onDelete('cascade')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
