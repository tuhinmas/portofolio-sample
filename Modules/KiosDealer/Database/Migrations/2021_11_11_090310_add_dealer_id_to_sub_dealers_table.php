<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDealerIdToSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->after("grading_id", function($table){
                $table->uuid("dealer_id")->nullable();
                $table->foreign("dealer_id")
                      ->references("id")
                      ->on("dealers")
                      ->onDelete("cascade");
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->dropForeign(["dealer_id"]);
            $table->dropColumn(["dealer_id"]);
        });
    }
}
