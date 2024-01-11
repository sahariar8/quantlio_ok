<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLisMedicationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lis_medication_details', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->string('medication_uuids')->nullable();
            $table->string('medication_name')->nullable();
            $table->string('metabolite_id')->nullable();
            $table->string('metabolite_name')->nullable();
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
        Schema::dropIfExists('lis_medication_details');
    }
}
