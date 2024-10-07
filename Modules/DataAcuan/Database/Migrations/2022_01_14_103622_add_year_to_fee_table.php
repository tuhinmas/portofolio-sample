<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYearToFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_products', function (Blueprint $table) {
            $table->after("id", function ($table) {
                $table->integer("year")->nullable();
                $table->enum("type", ["1", "2"])->comment("1 => reguler, 2 => target")->nullable();
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
        Schema::table('fee_products', function (Blueprint $table) {
            $table->dropColumn('year');
            $table->dropColumn('type');
        });
    }
}
