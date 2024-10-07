<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDealerIdInSubDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_dealers', function (Blueprint $table) {
            $table->renameColumn("dealer_id", "sub_dealer_id");
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
            $table->renameColumn("sub_dealer_id", "dealer_id");
        });
    }
}
