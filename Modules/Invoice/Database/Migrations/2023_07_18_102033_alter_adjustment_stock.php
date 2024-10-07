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
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->integer("product_unreceived_by_distributor")->default(0)->change();
            $table->integer("product_undelivered_by_distributor")->default(0)->change();
            $table->integer("previous_contract_return")->after('product_undelivered_by_distributor')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->dropColumn("previous_contract_return");
        });
    }
};
