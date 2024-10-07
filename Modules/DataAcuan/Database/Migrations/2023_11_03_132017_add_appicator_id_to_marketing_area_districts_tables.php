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
        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->uuid("applicator_id")->after("personel_id")->nullable();
            $table->foreign("applicator_id")
                ->references("id")
                ->on("personels");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_area_districts', function (Blueprint $table) {
            $table->dropForeign(["applicator_id"]);
            $table->dropColumn('applicator_id');
        });
    }
};
