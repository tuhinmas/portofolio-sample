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
            $table->uuid("agency_level_id")->nullable();
                $table->foreign("agency_level_id")
                    ->references("id")
                    ->on("agency_levels")
                    ->onUpdate("cascade");
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
            $table->dropForeign(['agency_level_id']);
            $table->dropColumn('agency_level_id');
        });
    }
};
