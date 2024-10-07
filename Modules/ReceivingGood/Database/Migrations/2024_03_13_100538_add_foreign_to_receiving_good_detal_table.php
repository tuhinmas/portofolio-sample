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
        Schema::table('receiving_good_details', function (Blueprint $table) {
            $table->uuid("receiving_good_id")->nullable()->change();
            $table->foreign("receiving_good_id")
                ->references("id")
                ->on("receiving_goods")
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
        Schema::table('receiving_good_details', function (Blueprint $table) {
            $table->dropForeign(['receiving_good_id']);
        });
    }
};
