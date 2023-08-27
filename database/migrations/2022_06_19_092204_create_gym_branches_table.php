<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGymBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gym_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->string('address');
            $table->string('location');
            $table->unsignedBigInteger('gym_id');
            $table->foreign('gym_id')->references('id')->on('gyms')->onDelete('cascade');
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
        Schema::dropIfExists('branches');
    }
}
