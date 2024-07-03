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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('numero_pedido');
            $table->foreignId('cliente_id')->references('id')->on('clientes');
            $table->bigInteger('numero_pedido_s3');
            $table->date('fecha_pedido');
            $table->date('fecha_entrega_pedido');
            $table->string('orden_compra_cliente', 128);
            $table->bigInteger('numero_lote');
            $table->string('estado_pedido', 3);
            $table->date('fecha_confirmacion')->nullable();
            $table->date('fecha_ejecucion')->nullable();
            $table->date('fecha_despacho')->nullable();
            $table->date('fecha_anulacion')->nullable();
            $table->string('observaciones', 128)->nullable();
            $table->boolean('estado')->default(1);
            
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
        Schema::dropIfExists('pedidos');
    }
};
