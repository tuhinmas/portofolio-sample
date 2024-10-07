<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('sales_orders')->delete();
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->after('store_id', function($table){
                $table->uuid('personel_id');
                $table->foreign('personel_id')
                      ->references('id')
                      ->on('personels')
                      ->onDelete('cascade');
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
    }
}
