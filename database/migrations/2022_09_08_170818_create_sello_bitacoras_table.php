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
        Schema::create('sellos_bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sello_id')->constrained('sellos');
            $table->foreignId('producto_id')->constrained('productos_clientes');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('producto_empaque_id')->nullable()->constrained('sellos');
            $table->foreignId('kit_id')->nullable()->constrained('kits');
            $table->string('tipo_empaque_despacho', 1);
            $table->foreignId('tipo_evento_id')->constrained('tipos_eventos');
            $table->date('fecha_evento');
            $table->string('estado_sello', 3);
            $table->bigInteger('numero_remision')->nullable();
            $table->foreignId('lugar_origen_id')->constrained('lugares');
            $table->foreignId('lugar_destino_id')->nullable()->constrained('lugares');
            $table->foreignId('usuario_destino_id')->nullable()->constrained('usuarios');
            $table->foreignId('contenedor_id')->nullable()->constrained('contenedores');
            $table->string('documento_referencia', 25)->nullable();
            $table->foreignId('lugar_instalacion_id')->nullable()->constrained('lugares');
            $table->foreignId('zona_instalacion_id')->nullable()->constrained('zonas_contenedores');
            $table->bigInteger('operacion_embarque_id')->nullable();
            $table->decimal('longitud', $precision = 10, $scale = 7)->nullable();
            $table->decimal('latitud', $precision = 10, $scale = 7)->nullable();
            $table->bigInteger('usuario_creacion_id');
            $table->string('usuario_creacion_nombre',128);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sellos_bitacora');
    }
};
