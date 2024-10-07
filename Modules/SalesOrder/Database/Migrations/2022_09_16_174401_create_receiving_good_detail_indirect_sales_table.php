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
        Schema::create('receiving_good_detail_indirect_sales', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("receiving_good_id");
            $table->string("status");
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("receiving_good_id")
                ->references("id")
                ->on("receiving_good_indirect_sales")
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
        Schema::dropIfExists('receiving_good_detail_indirect_sales');
    }
};
