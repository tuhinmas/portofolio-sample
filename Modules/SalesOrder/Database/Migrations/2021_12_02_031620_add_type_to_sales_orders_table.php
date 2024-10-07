<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->after("id", function($table){
                $table->enum("type", ["1", "2"])->comment("1 => d2d, 2 => dd2r, null => direct")->nullable();
            });
            
            $table->after("personel_id", function($table){
                $table->uuid("distributor_id")->nullable();
                $table->uuid("counter_id")->nullable();
                $table->double("counter_fee", 15, 2)->nullable();
                $table->enum("model", ["1", "2"])->comment("1 => dealer, 2 => sub_dealer")->nullable();
                $table->uuid("agency_level_id")->nullable();
                $table->foreign("counter_id")
                      ->references("id")
                      ->on("personels")
                      ->onDelete("cascade");
                
                $table->foreign("distributor_id")
                      ->references("id")
                      ->on("dealers")
                      ->onDelete("cascade");

                $table->foreign("agency_level_id")
                      ->references("id")
                      ->on("agency_levels")
                      ->onDelete("cascade");
            });

            $table->after("status_fee_id", function($table){
                $table->text("note")->nullable();
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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['counter_id']);
            $table->dropForeign(['distributor_id']);
            $table->dropForeign(['agency_level_id']);
            $table->dropColumn('type');
            $table->dropColumn('counter_id');
            $table->dropColumn('distributor_id');
            $table->dropColumn('agency_level_id');
            $table->dropColumn('counter_fee');
            $table->dropColumn('model');
            $table->dropColumn('note');
        });
    }
}
