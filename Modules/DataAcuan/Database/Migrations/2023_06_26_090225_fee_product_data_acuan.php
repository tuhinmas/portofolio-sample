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
        Schema::table('fee_products', function (Blueprint $table) {
            $table->after("year", function ($table) {
                $table->integer("quartal")->nullable();
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
            $table->dropColumn("quartal")->nullable();
        });
    }
};
