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
        Schema::create('pickup_orders', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("warehouse_id");
            $table->foreign("warehouse_id")
                ->references("id")
                ->on("warehouses")
                ->onDelete("cascade");

            $table->uuid("driver_id");
            $table->foreign("driver_id")
                ->references("id")
                ->on("drivers")
                ->onDelete("cascade");

            $table->string("pickup_number")->nullable();
            $table->dateTime("delivery_date")->nullable();
            $table->integer("status")->nullable()->comment("1 = Loading, 2 = Dikirim, 3 = Selesai, 4 = Dibatalkan;");

            $table->text ("note")->nullable();
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
        Schema::dropIfExists('pickup_orders');
    }
};
