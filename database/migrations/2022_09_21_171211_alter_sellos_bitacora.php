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
        Schema::table('sellos_bitacora', function (Blueprint $table) {
            $table->string('clase_evento', 1)->default('C')->nullable()->after('estado_sello');
            $table->bigInteger('numero_pedido')->nullable()->after('clase_evento');
            $table->unsignedBigInteger('lugar_origen_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sellos_bitacora', function (Blueprint $table) {
            $table->dropColumn('clase_evento');
            $table->dropColumn('numero_pedido');
        });
    }
};
