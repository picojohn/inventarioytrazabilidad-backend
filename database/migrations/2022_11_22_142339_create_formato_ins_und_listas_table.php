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
        Schema::create('formatos_ins_und_listas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formato_unidad_id')->constrained('formatos_inspeccion_unidades')->cascadeOnDelete();
            $table->foreignId('tipo_lista_id')->constrained('tipos_listas_chequeo')->cascadeOnDelete();
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
        Schema::dropIfExists('formatos_ins_und_listas');
    }
};
