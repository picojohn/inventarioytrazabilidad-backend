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
            $table->dropForeign(['producto_empaque_id']);
            $table->dropColumn('producto_empaque_id');
        });
        Schema::table('sellos', function (Blueprint $table) {
            $table->foreignId('producto_empaque_id')->nullable()->after('cliente_id')->references('id')->on('sellos');
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
            $table->dropForeign(['producto_empaque_id']);
            $table->dropColumn('producto_empaque_id');
        });
        Schema::table('sellos', function (Blueprint $table) {
            $table->foreignId('producto_empaque_id')->nullable()->after('cliente_id')->references('id')->on('clientes');
        });
    }
};
