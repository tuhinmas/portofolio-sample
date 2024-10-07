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
        Schema::table('sub_dealer_temps', function (Blueprint $table) {
            $table->uuid("submited_by")->after("store_id")->nullable();
            $table->foreign("submited_by")
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
        Schema::table('sub_dealer_temps', function (Blueprint $table) {
            $table->dropForeign(['submited_by']);
            $table->dropColumn('submited_by');
        });
    }
};
