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
        Schema::create('pedidos_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->references('id')->on('pedidos');
            $table->bigInteger('consecutivo_detalle');
            $table->foreignId('producto_id')->nullable()->references('id')->on('productos_clientes');
            $table->foreignId('kit_id')->nullable()->references('id')->on('kits');
            $table->integer('cantidad');
            $table->bigInteger('color_id')->nullable();
            $table->string('prefijo', 15)->nullable();
            $table->string('posfijo', 15)->nullable();
            $table->integer('longitud_serial')->nullable();
            $table->bigInteger('consecutivo_serie_inicial')->nullable();
            $table->string('serie_inicial_articulo', 128)->nullable();
            $table->string('serie_final_articulo', 128)->nullable();
            $table->string('longitud_sello', 128)->nullable();
            $table->string('diametro', 128)->nullable();
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
        Schema::dropIfExists('pedidos_detalle');
    }
};
