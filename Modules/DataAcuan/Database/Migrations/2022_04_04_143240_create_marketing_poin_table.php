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
        Schema::create('marketing_poin', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->integer("point");
            $table->uuid("product_id");
            $table->foreign("product_id")
                ->references("id")
                ->on("products")
                ->onDelete("cascade");
            $table->integer("quantity");
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
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
        Schema::dropIfExists('marketing_poin');
    }
};
