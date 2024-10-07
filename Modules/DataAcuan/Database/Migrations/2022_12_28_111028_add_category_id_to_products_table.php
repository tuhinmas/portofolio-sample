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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger("category_id")->after("category")->default(1);
            $table->foreign("category_id")
                ->references("id")
                ->on("product_categories")
                ->onDelete("cascade");
        });
       
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger("category")->default("1")->change();
            $table->foreign("category")
                ->references("id")
                ->on("product_categories")
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
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
       
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category']);
        });
    }
};
