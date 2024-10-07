<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adjustment_stock_marketings', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->date("opname_date");
            $table->string("real_stock");
            $table->uuid("dealer_id");
            $table->uuid("product_id");
            $table->integer("product_in_warehouse")->nullable()->comment("products ready on warehouse");
            $table->integer("product_unreceived_by_distributor")->nullable()->comment("products purchase by distributor and has not received by distributor");
            $table->integer("product_undelivered_by_distributor")->nullable()->comment("product sales by distributor and has not received by retailer");
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("product_id")
                ->references("id")
                ->on("products")
                ->onDelete("cascade");

            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adjustment_stock_marketings');
    }
};
