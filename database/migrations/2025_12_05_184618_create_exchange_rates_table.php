<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRatesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exchange_source_id');
            $table->decimal('buy_price', 10, 4);
            $table->decimal('sell_price', 10, 4);
            $table->string('currency_from', 3);
            $table->string('currency_to', 3);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('exchange_source_id')->references('id')->on('exchange_sources')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('exchange_rates');
    }
}
