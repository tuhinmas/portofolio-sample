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
            $table->dropForeign(['id_list_dispatch_order']);
            $table->dropColumn('id_list_dispatch_order');
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
            $table->uuid("id_list_dispatch_order");
        });
    }
};
