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
            $table->string('observaciones_evento')->nullable()->after('numero_instalacion_evidencia');
        });
        Schema::table('sellos', function (Blueprint $table) {
            $table->dropColumn('fecha_inhabilitacion');
            $table->dropColumn('fecha_devolucion_stock');
            $table->dropColumn('fecha_destruccion');
            $table->dropColumn('fecha_extravio');
            $table->foreignId('ultimo_tipo_evento_id')->nullable()->after('fecha_ultima_verificacion_previaje')->constrained('tipos_eventos');
            $table->date('fecha_ultimo_evento')->nullable()->after('ultimo_tipo_evento_id');
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
            $table->dropColumn('observaciones_evento');
        });
        Schema::table('sellos', function (Blueprint $table) {
            $table->date('fecha_inhabilitacion')->nullable();
            $table->date('fecha_devolucion_stock')->nullable();
            $table->date('fecha_destruccion')->nullable();
            $table->date('fecha_extravio')->nullable();
            $table->dropForeign(['ultimo_tipo_evento_id']);
            $table->dropColumn('ultimo_tipo_evento_id');
            $table->dropColumn('fecha_ultimo_evento');
        });
    }
};
