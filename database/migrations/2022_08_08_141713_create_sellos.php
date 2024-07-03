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
        Schema::create('sellos', function (Blueprint $table) {
            $table->id();
            $table->string('serial', 128);
            $table->string('serial_interno', 128)->nullable();
            $table->string('serial_qr', 128)->nullable();
            $table->string('serial_datamatrix', 128)->nullable();
            $table->string('serial_pdf', 128)->nullable();
            $table->string('serial_rfid', 128)->nullable();
            $table->string('serial_empacado', 128)->nullable();
            $table->foreignId('producto_id')->references('id')->on('productos_clientes');
            $table->bigInteger('producto_s3_id');
            $table->bigInteger('color_id')->nullable();
            $table->foreignId('cliente_id')->references('id')->on('clientes');
            $table->foreignId('producto_empaque_id')->nullable()->references('id')->on('clientes');
            $table->foreignId('kit_id')->nullable()->references('id')->on('kits');
            $table->string('tipo_empaque_despacho', 1);
            $table->string('estado_sello', 3)->default('GEN');
            $table->bigInteger('numero_pedido');
            $table->bigInteger('numero_ultima_remision')->nullable();
            $table->date('fecha_ultima_remision')->nullable();
            $table->time('hora_estimada_despacho')->nullable();
            $table->foreignId('user_id')->references('id')->on('usuarios');
            $table->foreignId('lugar_id')->references('id')->on('lugares');
            $table->date('fecha_ultima_recepción')->nullable();
            $table->foreignId('contenedor_id')->nullable()->references('id')->on('contenedores');
            $table->string('documento_referencia', 25)->nullable();
            $table->foreignId('lugar_instalacion_id')->nullable()->references('id')->on('lugares');
            $table->foreignId('zona_instalacion_id')->nullable()->references('id')->on('zonas_contenedores');
            $table->date('fecha_instalación')->nullable();
            $table->bigInteger('operacion_embarque_id')->nullable();
            $table->string('indicativo_previaje', 1)->default('N');
            $table->timestamp('fecha_instalacion_previaje')->nullable();
            $table->timestamp('fecha_ultima_verificacion_previaje')->nullable();
            $table->timestamp('fecha_inhabilitacion')->nullable();
            $table->timestamp('fecha_devolucion_stock')->nullable();
            $table->timestamp('fecha_destruccion')->nullable();
            $table->timestamp('fecha_extravio')->nullable();
            $table->string('tipo_inventario', 1)->default('S');
            
            // Auditoria
            $table->bigInteger('usuario_creacion_id');
            $table->string('usuario_creacion_nombre',128);
            $table->bigInteger('usuario_modificacion_id');
            $table->string('usuario_modificacion_nombre',128);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sellos');
    }
};
