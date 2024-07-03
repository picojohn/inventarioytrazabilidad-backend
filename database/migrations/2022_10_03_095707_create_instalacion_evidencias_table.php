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
        Schema::create('instalacion_evidencias', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('numero_instalacion_evidencia');
            $table->string('evidencia', 128);

            // Auditoria
            $table->bigInteger('usuario_creacion_id');
            $table->string('usuario_creacion_nombre',128);
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
        Schema::dropIfExists('instalacion_evidencias');
    }
};
