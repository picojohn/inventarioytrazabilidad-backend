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
        Schema::table('clientes', function (Blueprint $table) {
            $table->renameColumn('indicativo_instalacion_especial', 'indicativo_lectura_sellos_externos');
            $table->renameColumn('indicativo_lectura_contenedor', 'indicativo_instalacion_contenedor');
            $table->string('indicativo_operaciones_embarque', 1)->default('N')->after('indicativo_contenedor_exclusivo');
            $table->string('indicativo_instalacion_automatica', 1)->default('N')->after('indicativo_operaciones_embarque');
            $table->string('indicativo_registro_lugar_instalacion', 1)->default('N')->after('indicativo_instalacion_automatica');
            $table->string('indicativo_registro_zona_instalacion', 1)->default('N')->after('indicativo_registro_lugar_instalacion');
            $table->string('indicativo_asignacion_serial_automatica', 1)->default('N')->after('indicativo_registro_zona_instalacion');
            $table->string('indicativo_documento_referencia', 1)->default('N')->after('indicativo_asignacion_serial_automatica');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->renameColumn('indicativo_instalacion_contenedor', 'indicativo_lectura_contenedor');
            $table->renameColumn('indicativo_lectura_sellos_externos', 'indicativo_instalacion_especial');
            $table->dropColumn('indicativo_operaciones_embarque');
            $table->dropColumn('indicativo_instalacion_automatica');
            $table->dropColumn('indicativo_registro_lugar_instalacion');
            $table->dropColumn('indicativo_registro_zona_instalacion');
            $table->dropColumn('indicativo_asignacion_serial_automatica');
            $table->dropColumn('indicativo_documento_referencia');
        });
    }
};
