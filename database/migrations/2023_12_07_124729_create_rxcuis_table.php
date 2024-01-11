<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRxcuisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rxcuis', function (Blueprint $table) {
            $table->id();
            $table->string('drugsName');
            $table->string('RxCUI');
            $table->string('parentDrugName')->nullable();
            $table->string('parentRxcui')->nullable();
            $table->string('analyt')->nullable();
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
        Schema::dropIfExists('rxcuis');
    }
}
