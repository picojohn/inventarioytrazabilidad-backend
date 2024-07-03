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
        Schema::table('productos_clientes', function (Blueprint $table) {
            $table->string('codigo_externo_producto', 128)->nullable()->after('nombre_producto_cliente');
            $table->dropColumn('abreviatura_producto_cliente');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productos_clientes', function (Blueprint $table) {
            $table->dropColumn('codigo_externo_producto');
            $table->string('abreviatura_producto_cliente', 128)->nullable();
        });
    }
};
