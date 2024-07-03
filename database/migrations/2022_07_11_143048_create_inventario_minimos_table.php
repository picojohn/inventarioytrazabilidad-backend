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
        Schema::create('inventario_minimo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->nullable()->references('id')->on('kits');
            $table->foreignId('producto_cliente_id')->nullable()->references('id')->on('productos_clientes');
            $table->foreignId('cliente_id')->references('id')->on('clientes');
            $table->foreignId('lugar_id')->references('id')->on('lugares');
            $table->integer('cantidad_inventario_minimo');
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
        Schema::dropIfExists('inventario_minimo');
    }
};
