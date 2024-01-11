<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLisOrdersTestResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lis_orders_test_results', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->string('test_name')->nullable();
            $table->string('result_quantitative')->nullable();
            $table->string('result_qualitative')->nullable();
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
        Schema::dropIfExists('lis_orders_test_results');
    }
}
