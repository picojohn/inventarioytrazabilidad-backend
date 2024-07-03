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
            $table->string('indicativo_contenedor_exclusivo', 1)->after('indicativo_lectura_contenedor');
            $table->dropColumn('hash_id');
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
            $table->dropColumn('indicativo_contenedor_exclusivo');
            $table->string('hash_id', 128)->after('indicativo_lectura_contenedor');
        });
    }
};
