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
            $table->enum("status", ["planned", "delivered","canceled","received"])->nullable();
        });

        Schema::table('dispatch_promotions', function (Blueprint $table) {
            $table->enum("status", ["planned", "delivered","canceled","received"])->nullable();
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
            $table->dropColumn("status");
        });
        
        Schema::table('dispatch_promotions', function (Blueprint $table) {
            $table->dropColumn("status");
        });
    }
};
