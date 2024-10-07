<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCoreFarmerIdToCoreFarmerTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('core_farmer_temps', function (Blueprint $table) {
            $table->after("id", function($table){
                $table->uuid("core_farmer_id")->nullable();
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
        Schema::table('core_farmer_temps', function (Blueprint $table) {
        $table->dropColumn('core_farmer_id');
        });
    }
}
