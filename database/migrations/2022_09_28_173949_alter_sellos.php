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
            $table->dropColumn('fecha_instalación');
            $table->date('fecha_instalacion')->nullable()->after('zona_instalacion_id');
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
            $table->dropColumn('fecha_instalacion');
            $table->date('fecha_instalación')->nullable()->after('zona_instalacion_id');
        });
    }
};
