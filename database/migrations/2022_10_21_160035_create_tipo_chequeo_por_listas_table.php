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
        Schema::create('tipos_chequeos_por_lista', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_carga_id')->constrained('unidades_carga_transporte')->cascadeOnDelete();
            $table->foreignId('lista_chequeo_id')->constrained('tipos_listas_chequeo')->cascadeOnDelete();
            $table->foreignId('tipo_chequeo_id')->constrained('tipos_chequeos')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            
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
        Schema::dropIfExists('tipos_chequeos_por_lista');
    }
};
