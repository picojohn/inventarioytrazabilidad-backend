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
            $table->string('indicativo_encriptacion_producto', 1)->default('N')->after('abreviatura_producto_cliente');
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
            $table->dropColumn('indicativo_encriptacion_producto');
        });
    }
};
