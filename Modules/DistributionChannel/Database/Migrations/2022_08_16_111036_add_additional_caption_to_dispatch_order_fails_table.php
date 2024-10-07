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
        Schema::table('dispatch_order_files', function (Blueprint $table) {
            $table->string("additional_caption")->nullable()->after("caption");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispatch_order_files', function (Blueprint $table) {
            $table->dropColumn('additional_caption');
        });
    }
};
