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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn('code_dispatch_order');
            $table->dropColumn('driver_name');
            $table->dropColumn('document');
            $table->dropColumn('caption');
            $table->dropForeign(['id_dispatch_order']);
            $table->dropColumn('id_dispatch_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->string("code_dispatch_order");
        $table->string("driver_name");
        $table->string("document");
        $table->string("caption");
        $table->uuid("id_dispatch_order");
        $table->foreign("id_dispatch_order")
              ->references("id")
              ->on("discpatch_order");
    }
};
