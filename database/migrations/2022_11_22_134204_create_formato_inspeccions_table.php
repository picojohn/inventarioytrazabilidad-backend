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
        Schema::create('formatos_inspeccion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 128);
            $table->foreignId('clase_inspeccion_id')->constrained('clases_inspeccion')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
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
        Schema::dropIfExists('formatos_inspeccion');
    }
};
