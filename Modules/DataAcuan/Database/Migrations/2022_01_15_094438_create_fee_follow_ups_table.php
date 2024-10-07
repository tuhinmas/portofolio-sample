<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeeFollowUpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_follow_ups', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->integer("follow_up_days");
            $table->double("fee", 5, 2);
            $table->integer("settle_days");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fee_follow_ups');
    }
}
