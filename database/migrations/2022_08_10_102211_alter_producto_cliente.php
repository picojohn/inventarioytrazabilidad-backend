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
            $table->dropColumn('indicativo_encriptacion_producto');
            $table->string('indicativo_producto_externo', 1)->default('N')->after('codigo_externo_producto');
            $table->string('indicativo_producto_empaque', 1)->default('N')->after('indicativo_producto_externo');
            $table->integer('valor_serial_interno')->nullable()->after('indicativo_producto_empaque');
            $table->string('operador_serial_interno', 1)->nullable()->after('valor_serial_interno');
            $table->integer('valor_serial_qr')->nullable()->after('operador_serial_interno');
            $table->string('operador_serial_qr', 5)->nullable()->after('valor_serial_qr');
            $table->integer('valor_serial_datamatrix')->nullable()->after('operador_serial_qr');
            $table->string('operador_serial_datamatrix', 5)->nullable()->after('valor_serial_datamatrix');
            $table->integer('valor_serial_pdf')->nullable()->after('operador_serial_datamatrix');
            $table->string('operador_serial_pdf', 5)->nullable()->after('valor_serial_pdf');
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
            $table->string('indicativo_encriptacion_producto', 1)->nullable()->after('codigo_externo_producto');
            $table->dropColumn('indicativo_producto_externo');
            $table->dropColumn('indicativo_producto_empaque');
            $table->dropColumn('valor_serial_interno');
            $table->dropColumn('operador_serial_interno');
            $table->dropColumn('valor_serial_qr');
            $table->dropColumn('operador_serial_qr');
            $table->dropColumn('valor_serial_datamatrix');
            $table->dropColumn('operador_serial_datamatrix');
            $table->dropColumn('valor_serial_pdf');
            $table->dropColumn('operador_serial_pdf');
        });
    }
};
