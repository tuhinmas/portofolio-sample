<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->uuid("contract_id")->after("dealer_id")->nullable();
            $table->foreign("contract_id")
                ->references("id")
                ->on("distributor_contracts")
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
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->$table->dropColumn('contract_id');
        });
    }
};
