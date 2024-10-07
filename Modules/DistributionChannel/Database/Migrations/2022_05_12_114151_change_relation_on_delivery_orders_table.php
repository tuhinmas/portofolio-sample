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
        //
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropForeign(['id_list_dispatch_order']);
            $table->dropColumn(['id_list_dispatch_order']);
            $table->uuid("id_dispatch_order");
            $table->foreign("id_dispatch_order")
                    ->references("id")
                    ->on("discpatch_order")
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
        //
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropForeign(['id_dispatch_order']);
        });
    }
};
