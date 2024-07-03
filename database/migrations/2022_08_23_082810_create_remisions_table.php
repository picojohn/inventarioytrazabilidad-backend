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
        Schema::create('remisiones', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('numero_remision');
            $table->foreignId('cliente_id')->references('id')->on('clientes');
            $table->date('fecha_remision');
            $table->foreignId('lugar_envio_id')->references('id')->on('lugares');
            $table->foreignId('user_envio_id')->references('id')->on('usuarios');
            $table->foreignId('lugar_destino_id')->references('id')->on('lugares');
            $table->foreignId('user_destino_id')->references('id')->on('usuarios');
            $table->time('hora_estimada_envio');
            $table->bigInteger('transportador_id');
            $table->string('guia_transporte', 20);
            $table->string('indicativo_confirmacion_recepcion', 1)->default('S');
            $table->string('estado_remision', 3)->default('GEN');
            $table->date('fecha_aceptacion')->nullable();
            $table->date('fecha_rechazo')->nullable();
            $table->date('fecha_anulacion')->nullable();
            $table->string('observaciones_remision', 128)->nullable();
            $table->string('observaciones_rechazo', 128)->nullable();
            
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
        Schema::dropIfExists('remisiones');
    }
};
