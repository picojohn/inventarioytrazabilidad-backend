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
        Schema::create('remisiones_detalles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('numero_remision');
            $table->bigInteger('consecutivo_detalle');
            $table->foreignId('sello_id')->references('id')->on('sellos');
            $table->foreignId('producto_id')->references('id')->on('productos_clientes');
            $table->foreignId('kit_id')->nullable()->references('id')->on('kits');
            $table->string('serial', 128);
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
        Schema::dropIfExists('remisiones_detalles');
    }
};
