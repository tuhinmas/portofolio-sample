<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubDealerIdToStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->after("dealer_id", function($table){
                $table->uuid("sub_dealer_id")->nullable();
                $table->foreign("sub_dealer_id")
                      ->references("id")
                      ->on("sub_dealers")
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
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['sub_dealer_id']);
            $table->dropColumn('sub_dealer_id');
        });
    }
}
