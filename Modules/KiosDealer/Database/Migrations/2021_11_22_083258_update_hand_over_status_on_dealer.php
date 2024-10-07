<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHandOverStatusOnDealer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropForeign(['handover_status']);
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->renameColumn('handover_status', 'status_fee');
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->foreign("status_fee")
                ->references("id")
                ->on("status_fee");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropForeign(['status_fee']);
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->renameColumn('status_fee', 'handover_status');
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->foreign("handover_status")
                ->references("id")
                ->on("handovers");
        });
    }
}
