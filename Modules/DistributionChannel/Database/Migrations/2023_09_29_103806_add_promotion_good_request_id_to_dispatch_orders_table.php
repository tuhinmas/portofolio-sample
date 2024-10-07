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
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->uuid("promotion_good_request_id")->nullable()->after("invoice_id");
            $table->foreign("promotion_good_request_id")
                ->references("id")
                ->on("promotion_good_requests");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discpatch_order', function (Blueprint $table) {
            $table->dropForeign(['promotion_good_request_id']);
            $table->dropColumn('promotion_good_request_id');
        });
    }
};
