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
        Schema::create('lugares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 128);
            $table->string('direccion', 128)->nullable();
            $table->string('telefono', 128)->nullable();
            $table->foreignId('cliente_id')->references('id')->on('clientes');
            $table->string('codigo_externo_lugar', 128)->nullable();
            $table->string('tipo_lugar', 2);
            $table->string('indicativo_lugar_remision', 1)->default('N');
            $table->string('indicativo_lugar_inspeccion', 1)->default('N');
            $table->string('observaciones', 128)->nullable();
            $table->boolean('estado')->default(true);

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
        Schema::dropIfExists('lugares');
    }
};
