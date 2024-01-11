<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetabolitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    // Example migration file content
    public function up()
    {
        Schema::create('metabolites', function (Blueprint $table) {
            $table->id();
            $table->string('testName');
            $table->string('class');
            $table->string('parent')->nullable();
            $table->string('metabolite')->nullable();
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
        Schema::dropIfExists('metabolites');
    }
}