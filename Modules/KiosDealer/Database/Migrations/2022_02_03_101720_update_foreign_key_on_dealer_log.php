<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeyOnDealerLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_logs', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });

        Schema::table('dealer_logs', function (Blueprint $table) {
            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers")
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
        Schema::table('dealer_logs', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
        });

        Schema::table('dealer_logs', function (Blueprint $table) {
            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers")
                ->onDelete("cascade");
        });
    }
}
