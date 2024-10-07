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
            $table->after("is_first_stock", function($table){
                $table->integer("self_sales")->nullable();
                $table->integer("must_return")->nullable();
            });
            
            $table->integer("previous_stock")->after("current_stock")->nullable();
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
            $table->dropColumn('self_sales');
            $table->dropColumn('must_return');
            $table->dropColumn('previous_stock');
        });
    }
};
