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
        Schema::table('price_histories', function (Blueprint $table) {
            $table->double("het")->after("price")->nullable();
            $table->integer("minimum_order")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('price_histories', function (Blueprint $table) {
            $table->dropColumn('het');
            $table->dropColumn('minimum_order');
        });
    }
};
