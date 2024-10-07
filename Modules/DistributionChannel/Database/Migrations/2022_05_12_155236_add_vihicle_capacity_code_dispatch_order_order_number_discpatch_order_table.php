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
            $table->integer('vihicle_capacity')->nullable();
            $table->string('code_dispatch_order')->nullable();
            $table->integer("order_number")->nullable();
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
            $table->integer('vihicle_capacity')->nullable();
            $table->string('code_dispatch_order')->nullable();
            $table->integer("order_number")->nullable();
        });
    }
};
