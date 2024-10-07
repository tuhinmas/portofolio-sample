<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteTotalFromFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee', function (Blueprint $table) {
            $table->dropColumn('total');
            $table->decimal("fee_per_item", 15, 2)->change();
            $table->renameColumn('taget', 'target');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee', function (Blueprint $table) {
            $table->integer("total");
            $table->renameColumn('target', 'taget');
        });
    }
}
