<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('state')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_age')->nullable();
            $table->string('patient_gender')->nullable();
            $table->date('reported_date')->nullable();
            $table->integer('location_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('state')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_age')->nullable();
            $table->string('patient_gender')->nullable();
            $table->date('reported_date')->nullable();
            $table->integer('location_id')->nullable();
        });
    }
}
