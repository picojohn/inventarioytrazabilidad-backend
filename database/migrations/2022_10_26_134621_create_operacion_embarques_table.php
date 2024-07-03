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
        Schema::create('operaciones_embarque', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 128);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('indicativo_requiere_instalacion_previaje', 1);
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('observaciones', 128)->nullable();
            $table->string('estado', 3)->default('VIG');
            
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
        Schema::dropIfExists('operaciones_embarque');
    }
};
