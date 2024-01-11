<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLisOrdersDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lis_orders_details', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->string('in_house_lab_locations')->nullable();
            $table->string('patient_name')->nullable();
            $table->date('patient_dob')->nullable();
            $table->string('patient_gender')->nullable();
            $table->string('patient_phone_number')->nullable();
			$table->string('account_name')->nullable();
			$table->string('provider_first_name')->nullable();
			$table->string('provider_last_name')->nullable();
            $table->string('accession_number')->nullable();
			$table->string('clia_sample_type')->nullable();
			$table->date('sample_collection_date')->nullable();
			$table->date('received_date')->nullable();
            $table->json('icd_codes')->nullable();
            $table->json('medication_uuids')->nullable();
            $table->string('test_panel_type')->nullable();

            $table->integer('provider_npi')->nullable();
            $table->text('prescribed_medications')->nullable();
            $table->text('drug_drug_interactions')->nullable();
            $table->text('contraindicated_conditions')->nullable();
            $table->text('boxed_warnings')->nullable();
            $table->tinyInteger('report_status')->default('0');
            $table->longText('order_test_result')->nullable();

            $table->string('account_location')->nullable();
			$table->string('state')->nullable();
			$table->string('patient_age')->nullable();
			$table->date('reported_date')->nullable();
			$table->integer('location_id')->length(11)->nullable();

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
        Schema::dropIfExists('lis_orders_details');
    }
}
