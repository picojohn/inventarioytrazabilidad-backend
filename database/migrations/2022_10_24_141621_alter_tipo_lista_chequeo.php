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
        Schema::table('tipos_listas_chequeo', function (Blueprint $table) {
            $table->foreignId('clase_inspeccion_id')->after('unidad_carga_id')->nullable()->constrained('clases_inspeccion')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipos_listas_chequeo', function (Blueprint $table) {
            $table->dropForeign(['clase_inspeccion_id']);
            $table->dropColumn('clase_inspeccion_id');
        });
    }
};
