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
        Schema::create('fee_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("personel_id");
            $table->uuid("position_id");
            $table->uuid("sales_order_origin_id");
            $table->double("fee_percentage",5,2);
            $table->double("fee_shared", 20, 2);
            $table->uuid("status_fee");
            $table->tinyInteger("handover_status");
            $table->tinyInteger("is_checked")->default(0);
            $table->tinyInteger("is_returned")->default(0);
            $table->string("fee_status");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onDelete("cascade");
                
            $table->foreign("position_id")
                ->references("id")
                ->on("positions")
                ->onDelete("cascade");

            $table->foreign("sales_order_origin_id")
                ->references("id")
                ->on("sales_order_origins")
                ->onDelete("cascade");

            $table->foreign("status_fee")
                ->references("id")
                ->on("status_fee")
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
        Schema::dropIfExists('fee_sharing_so_origins');
    }
};
