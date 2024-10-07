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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->after("date_delivered", function($table) {  
                $table->uuid("operational_manager_id")->nullable();
                $table->foreign('operational_manager_id')
                        ->references('id')
                        ->on('personels')
                        ->onDelete('cascade');
                $table->uuid("marketing_id")->nullable();
                $table->foreign('marketing_id')
                        ->references('id')
                        ->on('personels')
                        ->onDelete('cascade');
                $table->uuid("dealer_id")->nullable();
                $table->foreign('dealer_id')
                        ->references('id')
                        ->on('dealers')
                        ->onDelete('cascade');
                $table->string("driver_name")->nullable();
                $table->string('code_dispatch_order')->nullable();
                $table->integer("order_number")->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_orders', function (Blueprint $table) {

        });
    }
};
