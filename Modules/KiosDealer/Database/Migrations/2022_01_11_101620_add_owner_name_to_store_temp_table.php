<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnerNameToStoreTempTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_temps', function (Blueprint $table) {
            $table->after("name", function ($table) {
                $table->string("owner_name")->nullable();
            });
        });

        Schema::table('store_temps', function (Blueprint $table) {
            $table->after("telephone", function ($table) {
                $table->string("second_telephone")->nullable();
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
        Schema::table('store_temps', function (Blueprint $table) {
            $table->dropColumn('owner_name');
            $table->dropColumn('second_telephone');
        });
    }
}
