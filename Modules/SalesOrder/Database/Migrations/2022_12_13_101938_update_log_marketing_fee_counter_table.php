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
        Schema::table('log_marketing_fee_counter', function (Blueprint $table) {
            $table->uuid("fee_sharing_origin_id")->nullable()->change();
        });
       
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("sales_order_origin_id")->nullable()->change();
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

        });
    }
};
