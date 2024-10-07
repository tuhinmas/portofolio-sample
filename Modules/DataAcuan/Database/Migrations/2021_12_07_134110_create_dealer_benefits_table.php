<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealerBenefitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dealer_benefits', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->unsignedBigInteger("grading_id");
            $table->uuid("payment_method_id");
            $table->uuid("agency_level_id");
            $table->double("minimum_nominal", 15, 2);
            $table->double("discount", 5, 2);
            $table->enum("type", ["1", "2", "3", "4"])->comment("1 => Always, 2 => Threshold, 3 => Multiple Regretion, 4 => Multiple Progression");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("grading_id")
                  ->references("id")
                  ->on("gradings")
                  ->onDelete("cascade");
            $table->foreign("payment_method_id")
                  ->references("id")
                  ->on("payment_methods")
                  ->onDelete("cascade");
            $table->foreign("agency_level_id")
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
        Schema::dropIfExists('dealer_benefits');
    }
}
