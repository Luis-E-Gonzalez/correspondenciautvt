<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMensajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->bigIncrements('idm');
            $table->unsignedBigInteger('idu_users');
            $table->unsignedBigInteger('idac_actividades');
            $table->string('mensaje');
            $table->dateTime('fecha');
            $table->foreign('idu_users')->references('idu')->on('users');
            $table->foreign('idac_actividades')->references('idac')->on('actividades');
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
        Schema::dropIfExists('mensajes');
    }
}
