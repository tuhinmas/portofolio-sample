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
        Schema::create('adjustment_stock', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->date("opname_date");
            $table->string("real_stock");
            $table->string("current_stock");
            $table->uuid("product_id");
            $table->foreign("product_id")
                ->references("id")
                ->on("products")
                ->onDelete("cascade");
            $table->uuid("dealer_id");
            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers")
                ->onDelete("cascade");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adjustment_stock');
    }
};
