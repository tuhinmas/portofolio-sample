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
        Schema::create('credit_memo_details', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("credit_memo_id");
            $table->uuid("product_id");
            $table->string("package_name")->comment("package according sales order detail");
            $table->integer("quantity_on_package")->comment("package according sales order detail");
            $table->integer("quantity_order");
            $table->integer("quantity_return");
            $table->double("unit_price", 20, 2)->commane("price include discount, prive - (discount / qty)");
            $table->double("unit_price_return", 20, 2);
            $table->double("total", 20, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("credit_memo_id")
                ->references("id")
                ->on("credit_memos")
                ->onDelete("cascade");

            $table->foreign("product_id")
                ->references("id")
                ->on("products")
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
        Schema::dropIfExists('credit_memo_details');
    }
};
