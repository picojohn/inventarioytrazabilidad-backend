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
        Schema::table('tipos_eventos', function (Blueprint $table) {
            $table->string('indicativo_evento_manual', 1)->default('N')->after('estado_sello');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipos_eventos', function (Blueprint $table) {
            $table->dropColumn('indicativo_evento_manual');
        });
    }
};
