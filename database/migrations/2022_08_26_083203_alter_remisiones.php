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
        Schema::table('remisiones', function (Blueprint $table) {
            $table->dropColumn('transportador_id');
            $table->string('transportador', 128)->nullable()->after('hora_estimada_envio');
            $table->string('guia_transporte', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remisiones', function (Blueprint $table) {
            $table->dropColumn('transportador');
            $table->bigInteger('transportador_id')->nullable()->after('hora_estimada_envio');
            $table->string('guia_transporte', 20)->nullable()->change();
        });
    }
};
