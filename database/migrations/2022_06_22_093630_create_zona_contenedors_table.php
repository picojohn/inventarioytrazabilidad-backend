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
        Schema::create('zonas_contenedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 128);
            $table->integer('numero_sellos_zona')->nullable();
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
        Schema::dropIfExists('zonas_contenedores');
    }
};
