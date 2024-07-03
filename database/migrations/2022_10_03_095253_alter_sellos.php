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
        Schema::table('sellos', function (Blueprint $table) {
            $table->bigInteger('numero_instalacion_evidencia')->nullable()->after('operacion_embarque_id');
        });
        Schema::table('sellos_bitacora', function (Blueprint $table) {
            $table->bigInteger('numero_instalacion_evidencia')->nullable()->after('latitud');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sellos', function (Blueprint $table) {
            $table->dropColumn('numero_instalacion_evidencia');
        });
        Schema::table('sellos_bitacora', function (Blueprint $table) {
            $table->dropColumn('numero_instalacion_evidencia');
        });
    }
};
