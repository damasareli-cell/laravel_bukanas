<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   

    public function up()
{
    Schema::table('pedido', function (Blueprint $table) {
        $table->string('direccion_entrega')->after('cliente_dni');
        $table->string('metodo_pago')->after('direccion_entrega');
    });
}
};
