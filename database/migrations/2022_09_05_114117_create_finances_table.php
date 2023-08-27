<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->integer('base_salary');
            $table->integer('pay_cut')->nullable();
            $table->integer('pay_cut_string')->nullable();
            $table->integer('final_salary');

            $table->unsignedBigInteger('gym_trainer_id')->nullable();
            $table->foreign('gym_trainer_id')->references('id')->on('gym_trainers')->onDelete('cascade');

            $table->enum('month' , ['January','February','March','April','May','June','July','August','September','October','November','December']);
            $table->year('year');
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
        Schema::dropIfExists('finances');
    }
}
