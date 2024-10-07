<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateForeignOnCoreFaremrTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('core_farmer_temps', function (Blueprint $table) {
            $table->dropForeign(['store_temp_id']);
        });
        Schema::table('core_farmer_temps', function (Blueprint $table) {
            $table->foreign('store_temp_id')
                ->references('id')
                ->on('store_temps')
                ->onDelete('cascade');
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

        });
    }
}
