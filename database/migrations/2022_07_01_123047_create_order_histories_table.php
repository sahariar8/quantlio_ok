<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();
            $table->text('order_code')->nullable();
            $table->string('account_name')->nullable();
            $table->string('patient_name')->nullable();
            $table->text('medications')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('accession')->nullable();
            $table->string('in_house_lab_location')->nullable();
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
        Schema::dropIfExists('order_histories');
    }
}
