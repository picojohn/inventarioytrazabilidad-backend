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
        Schema::create('clientes_inspectores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->bigInteger('tipo_documento_id');
            $table->string('numero_documento', 128);
            $table->string('nombre_inspector', 128);
            $table->string('celular_inspector', 128)->nullable();
            $table->string('correo_inspector', 128)->nullable();
            $table->string('indicativo_formado_inspeccion', 1)->default('S');
            $table->date('fecha_ultima_formacion')->nullable();
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
        Schema::dropIfExists('clientes_inspectores');
    }
};
