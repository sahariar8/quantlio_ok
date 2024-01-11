<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->text('order_code')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_location')->nullable();
            $table->text('icd_codes')->nullable();
            $table->string('provider_name')->nullable();
            $table->integer('provider_npi')->nullable();
            $table->string('in_house_lab_location')->nullable();
            $table->date('patient_DOB')->nullable();
            $table->text('prescribed_medications')->nullable();
            $table->text('drug_drug_interactions')->nullable();
            $table->text('contraindicated_conditions')->nullable();
            $table->text('boxed_warnings')->nullable();
            $table->tinyInteger('report_status')->default('0');
            $table->longText('order_test_result')->nullable();
            $table->timestamps();
            $table->string('state')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_age')->nullable();
            $table->string('patient_gender')->nullable();
            $table->date('reported_date')->nullable();
            $table->integer('location_id')->length(11)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_details');
    }
}
