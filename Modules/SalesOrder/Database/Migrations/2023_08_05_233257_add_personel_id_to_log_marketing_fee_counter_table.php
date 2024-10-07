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
            $table->uuid("personel_id")->after("sales_order_id")->nullable();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
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
        Schema::table('log_marketing_fee_counter', function (Blueprint $table) {
            $table->dropForeign(['personel_id']);
            $table->dropColumn('personel_id');
        });
    }
};
