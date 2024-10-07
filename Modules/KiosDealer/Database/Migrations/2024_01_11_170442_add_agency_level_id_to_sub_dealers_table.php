<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->uuid("agency_level_id")->after("owner_telephone")->nullable();
            $table->foreign("agency_level_id")
                ->references("id")
                ->on("agency_levels")
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
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->dropForeign(['agency_level_id']);
            $table->dropColumn('agency_level_id');
        });
    }
};
