<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropHandOverStatusFromSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->dropForeign(['handover_status']);
            $table->dropColumn('handover_status');
        });

        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->after("note", function ($table) {
                $table->uuid("status_fee")->nullable();
                $table->foreign("status_fee")
                    ->references("id")
                    ->on("status_fee");
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->uuid("handover_status")->nullable();
        $table->foreign("handover_status")
            ->references("id")
            ->on("handovers")
            ->onDelete("cascade");

        $table->dropForeign(['status_fee']);
        $table->dropColumn('status_fee');
    }
}
