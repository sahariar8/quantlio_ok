<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugInteractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('prescribed_test')->nullable();
            $table->string('interacted_with')->nullable();
            $table->string('description')->nullable();
            $table->string('drug_class')->nullable();
            $table->string('keyword')->nullable();
            $table->integer('risk_score')->nullable();
            $table->string('order_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drug_interactions');
    }
}
