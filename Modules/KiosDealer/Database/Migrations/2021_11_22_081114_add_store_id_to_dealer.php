<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStoreIdToDealer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->after("grading_id", function($table){
                $table->uuid("store_id")->nullable();
                $table->uuid("sub_dealer_id")->nullable();
            });
        });

        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->foreign("store_id")
                  ->references("id")
                  ->on("stores");
                  
            $table->foreign("sub_dealer_id")
                  ->references("id")
                  ->on("sub_dealers");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
            $table->dropForeign(['sub_dealer_id']);
            $table->dropColumn('sub_dealer_id');
        });
    }
}
