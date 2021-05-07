<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmlogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmlogs', function (Blueprint $table) {
            $table->id();
            $table->string('cm');
            $table->string('board')->nullable();
            $table->string('ip')->nullable();
            $table->enum('loglevel', ['info', 'warning', 'error', 'debug']);
            $table->text('msg');
            $table->timestamps();
            $table->index('cm');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmlogs');
    }
}
