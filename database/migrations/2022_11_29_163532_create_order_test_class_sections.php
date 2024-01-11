<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTestClassSections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_test_class_sections', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('section_id');
            $table->string('test')->nullable();
            $table->string('test_class')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_test_class_sections');
    }
}
