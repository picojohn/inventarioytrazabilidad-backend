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
        Schema::table('lugares', function (Blueprint $table) {
            $table->dropColumn('codigo_externo_lugar');
        });
        Schema::table('lugares', function (Blueprint $table) {
            $table->string('indicativo_lugar_instalacion', 1)->after('indicativo_lugar_remision')->default('N');
            $table->string('codigo_externo_lugar', 128)->after('indicativo_lugar_inspeccion');
            $table->bigInteger('geocerca_id')->after('codigo_externo_lugar');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lugares', function (Blueprint $table) {
            $table->dropColumn('indicativo_lugar_instalacion');
            $table->dropColumn('geocerca_id');
        });
    }
};
