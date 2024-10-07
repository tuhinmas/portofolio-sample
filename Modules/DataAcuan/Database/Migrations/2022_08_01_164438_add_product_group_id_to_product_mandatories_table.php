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
        Schema::table('product_mandatories', function (Blueprint $table) {
            $table->uuid("product_group_id")->after("period_date")->nullable();
            $table->foreign("product_group_id")
                ->references("id")
                ->on("product_groups")
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
        Schema::table('product_mandatories', function (Blueprint $table) {
            $table->dropForeign(['product_group_id']);
            $table->dropColumn('product_group_id');
        });
    }
};
