<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('device', ['cm4']);
            $table->string('storage');
            $table->foreignId('image_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('label_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->enum('label_moment', ['never','preinstall','postinstall']);
            $table->string('eeprom_firmware')->nullable();
            $table->text('eeprom_settings')->nullable();
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
        Schema::dropIfExists('projects');
    }
}
