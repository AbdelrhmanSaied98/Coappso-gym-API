<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendences', function (Blueprint $table) {
            $table->id();
            $table->enum('type' , ['presence','absent']);
            $table->time('time_attendance')->nullable();
            $table->time('time_checkout')->nullable();
            $table->string('absent_type')->nullable();
            $table->date('date');

            $table->unsignedBigInteger('gym_trainer_id')->nullable();
            $table->foreign('gym_trainer_id')->references('id')->on('gym_trainers')->onDelete('cascade');

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
        Schema::dropIfExists('attendences');
    }
}
