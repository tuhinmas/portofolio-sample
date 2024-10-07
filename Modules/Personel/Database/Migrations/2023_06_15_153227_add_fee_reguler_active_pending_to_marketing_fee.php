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
        Schema::table('marketing_fee', function (Blueprint $table) {
            $table->double("fee_reguler_settle_pending", 20, 2)->after("fee_reguler_settle")->nullable();
        });

        Schema::table('marketing_fee', function (Blueprint $table) {
            $table->double("fee_target_settle_pending", 20, 2)->after("fee_target_settle")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_fee', function (Blueprint $table) {
            $table->dropColumn('fee_reguler_settle_pending');
            $table->dropColumn('fee_target_settle_pending');
        });
    }
};
