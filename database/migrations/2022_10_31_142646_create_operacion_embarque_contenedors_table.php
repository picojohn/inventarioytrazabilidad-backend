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
        Schema::create('operaciones_embarque_contenedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_embarque_id')->constrained('operaciones_embarque')->cascadeOnDelete();
            $table->foreignId('contenedor_id')->constrained('contenedores')->cascadeOnDelete();
            $table->string('estado_contenedor', 3)->default('ASG');
            $table->string('observaciones', 128)->nullable();
            
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
        Schema::dropIfExists('operaciones_embarque_contenedores');
    }
};
