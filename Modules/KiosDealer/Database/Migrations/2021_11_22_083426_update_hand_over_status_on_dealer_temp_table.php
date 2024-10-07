<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateHandOverStatusOnDealerTempTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->dropForeign(['handover_status']);
        });

        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->renameColumn('handover_status', 'status_fee');
        });

        Schema::table('dealer_temps', function (Blueprint $table) {
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
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->dropForeign(['status_fee']);
        });

        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->renameColumn('status_fee', 'handover_status');
        });

        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->foreign("handover_status")
                ->references("id")
                ->on("handovers");
        });
    }
}
