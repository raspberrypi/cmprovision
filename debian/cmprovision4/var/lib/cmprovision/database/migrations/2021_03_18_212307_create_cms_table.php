<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique();
            $table->string('mac');
            $table->string('model')->nullable();
            $table->integer('memory_in_gb')->nullable();
            $table->integer('storage')->nullable();
            $table->string('cid')->nullable();
            $table->string('csd')->nullable();            
            $table->string('firmware')->nullable();
            $table->string('image_filename')->nullable();
            $table->string('image_sha256')->nullable();
            $table->string('pre_script_output')->nullable();
            $table->string('post_script_output')->nullable();
            $table->integer('script_return_code')->nullable();
            $table->string('temp1')->nullable();
            $table->string('temp2')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('provisioning_board')->nullable();
            $table->timestamp('provisioning_started_at')->nullable();
            $table->timestamp('provisioning_complete_at')->nullable();
            $table->timestamps();
            $table->index('provisioning_started_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cms');
    }
}
