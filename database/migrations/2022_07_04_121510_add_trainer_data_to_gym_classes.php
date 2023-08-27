<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrainerDataToGymClasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->string('trainer_name');
            $table->string('trainer_image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->dropColumn('trainer_name');
            $table->dropColumn('trainer_image');
        });
    }
}
