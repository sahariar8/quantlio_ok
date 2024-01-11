<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lab_locations', function (Blueprint $table) {
            $table->id();
            $table->string('location', 255)->nullable();
            $table->string('printable_location', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('director', 255)->nullable();
            $table->string('CLIA', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('fax', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('logo_image', 255)->nullable();
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
        Schema::dropIfExists('lab_locations');
    }
}
