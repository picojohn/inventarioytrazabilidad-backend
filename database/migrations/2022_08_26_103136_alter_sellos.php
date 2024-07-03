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
            $table->dropColumn('fecha_ultima_recepción');
            $table->date('fecha_ultima_recepcion')->nullable()->after('lugar_id');
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
            $table->dropColumn('fecha_ultima_recepcion');
            $table->date('fecha_ultima_recepción')->nullable()->after('lugar_id');
        });
    }
};
