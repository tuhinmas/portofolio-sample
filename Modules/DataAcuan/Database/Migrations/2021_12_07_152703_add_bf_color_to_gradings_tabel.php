<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBfColorToGradingsTabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gradings', function (Blueprint $table) {
            $table->after("name", function($table){
                $table->string("bg_color")->nullable();
                $table->string("fore_color")->nullable();
                $table->double("credit_limit", 25, 2)->nullable();
            });
        });
        Schema::table('gradings', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gradings', function (Blueprint $table) {
            $table->dropColumn('bg_color');
            $table->dropColumn('fore_color');
            $table->dropColumn('credit_limit');
            $table->after("name", function($table){
                $table->string("color");
            });
        });
    }
}
