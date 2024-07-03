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
            $table->dropColumn('direccion');
            $table->dropColumn('telefono');
            $table->string('observaciones')->nullable()->after('asociado_id');
            
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
            $table->string('direccion', 128);
            $table->string('telefono', 128);
            $table->dropColumn('observaciones');
        });
    }
};
