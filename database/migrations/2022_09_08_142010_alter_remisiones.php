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
            $table->renameColumn('cliente_id', 'cliente_envio_id');
            $table->renameIndex('remisiones_cliente_id_foreign', 'remisiones_cliente_envio_id_foreign');
            $table->foreignId('cliente_destino_id')->after('cliente_id')->nullable()->constrained('clientes');
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
            $table->renameColumn('cliente_envio_id', 'cliente_id');
            $table->renameIndex('remisiones_cliente_envio_id_foreign', 'remisiones_cliente_id_foreign');
            $table->dropForeign(['cliente_destino_id']);
            $table->dropColumn('cliente_destino_id');
        });
    }
};
