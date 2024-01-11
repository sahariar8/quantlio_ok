<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderCodeQueueToProcess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_code_queues', function (Blueprint $table) {
            $table->id();
            $table->string('orderCode');
            $table->integer('workStatus')->default(0);
            $table->integer('numberOfFailur')->default(0);
            $table->string('startedAt')->nullable();
            $table->string('endAt')->nullable();
            //$table->timestamps();
            $table->timestamp('created_at')->default(now());
            $table->timestamp('updated_at')->nullable(); // ->default(now());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_code_queues');
    }
}
