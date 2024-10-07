<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_positions', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("position_id");
            $table->double("fee", 5, 2);
            $table->tinyInteger("follow_up")->default(0);
            $table->double("fee_cash", 5, 2)->nullable();
            $table->uuid("fee_cash_minimum_order");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("position_id")
                ->references("id")
                ->on("positions")
                ->onDelete("cascade");

            $table->foreign("fee_cash_minimum_order")
                ->references("id")
                ->on("agency_levels")
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
        Schema::dropIfExists('fee_positions');
    }
}
