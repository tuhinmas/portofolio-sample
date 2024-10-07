<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableReceivingGoodDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receiving_good_details', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("product_id")->foreign("product_id")
                  ->references("id")
                  ->on("products")
                  ->onUpdate("cascade");
            $table->uuid("receiving_good_id")->foreign("receiving_good_id")
                  ->references("id")
                  ->on("receiving_goods")
                  ->onUpdate("cascade")
                  ->onDelete("cascade");
            $table->uuid("user_id")->foreign("user_id")
                  ->references("id")
                  ->on("users")
                  ->onUpdate("cascade");
            $table->integer('quantity')->default(0);
            $table->string('status')->default("delivered");
            $table->text('note')->nullable();
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
        Schema::dropIfExists('');
    }
}
