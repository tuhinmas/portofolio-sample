<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class RenamFromFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $fee = "fee";
        $fee_targets = "fee_targets";
        Schema::rename($fee, $fee_targets);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $fee = "fee";
        $fee_targets = "fee_targets";
        Schema::rename($fee_targets, $fee);
    }
}
