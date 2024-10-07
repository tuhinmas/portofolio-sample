<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustSubToStoresTable extends Migration
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
                $table->tinyInteger("cust_sub")->nullable()->default(0);
            });
        });
        Schema::table('stores', function (Blueprint $table) {
            $table->after("cust_sub", function($table){
                $table->uuid("cust_distri")->nullable();
                $table->foreign("cust_distri")
                      ->references("id")
                      ->on("dealers");
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
            $table->dropColumn('cust_sub');
            $table->dropColumn('cust_distri');
        });
    }
}
