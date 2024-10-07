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
        Schema::table('fee_products', function (Blueprint $table) {
            $table->uuid("product_as")->after("product_id")->nullable();
            $table->foreign("product_as")
                ->references("id")
                ->on("products")
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
        Schema::table('fee_products', function (Blueprint $table) {
            $table->dropForeign(["product_as"]);
            $table->dropColumn('product_as');
        });
    }
};
