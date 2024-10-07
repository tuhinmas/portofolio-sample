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
        Schema::table('receiving_good_indirect_sales', function (Blueprint $table) {
            $table->after("status", function($table){
                $table->tinyInteger("receiving_type")->nullable()->comment("1 => selft accepted, 2 => delivered");
                $table->string("shipping_number")->nullable()->comment("if receiving type is delivered");
            });
            
            $table->dropColumn('delivery_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receiving_good_indirect_sales', function (Blueprint $table) {
            $table->dropColumn('receiving_type');
            $table->dropColumn('shipping_number');
            $table->string("delivery_number");
        });
    }
};
