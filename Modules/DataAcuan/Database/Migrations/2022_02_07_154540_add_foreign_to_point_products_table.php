<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignToPointProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('point_products', function (Blueprint $table) {
            $table->dropForeign(["product_id"]);
        });

        Schema::table('point_products', function (Blueprint $table) {
            $table->foreign(["product_id"])
                  ->references("id")
                  ->on("products")
                  ->onDelete("cascade")
                  ->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('point_products', function (Blueprint $table) {
            Schema::table('point_products', function (Blueprint $table) {
                $table->dropForeign(["product_id"]);
            });
    
            Schema::table('point_products', function (Blueprint $table) {
                $table->foreign(["product_id"])
                      ->references("id")
                      ->on("products")
                      ->onDelete("cascade")
                      ->onUpdate("cascade");
            });
        });
    }
}
