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
        Schema::table('log_marketing_fee_counter', function (Blueprint $table) {
            $table->uuid("fee_sharing_origin_id")->after("id");
            $table->foreign("fee_sharing_origin_id")
                ->references("id")
                ->on("fee_sharing_so_origins")
                ->ondelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_marketing_fee_counter', function (Blueprint $table) {
            $table->dropForeign(['fee_sharing_origin_id']);
            $table->dropColumn('fee_sharing_origin_id');
        });
    }
};
