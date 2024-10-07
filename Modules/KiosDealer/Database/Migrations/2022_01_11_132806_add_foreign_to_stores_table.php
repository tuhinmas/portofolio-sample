<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignToStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->foreign("district_id")
                ->references("id")
                ->on("indonesia_districts")
                ->onDelete("cascade");

            $table->foreign("city_id")
                ->references("id")
                ->on("indonesia_cities")
                ->onDelete("cascade");

            $table->foreign("province_id")
                ->references("id")
                ->on("indonesia_provinces")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['province_id']);
        });
    }
}
