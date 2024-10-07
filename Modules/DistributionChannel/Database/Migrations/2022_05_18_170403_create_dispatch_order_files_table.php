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
        Schema::create('dispatch_order_files', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid("dispatch_orders_id");
            $table->foreign("dispatch_orders_id")
                    ->references("id")
                    ->on("discpatch_order")
                    ->onDelete("cascade");
            $table->string('document')->nullable();
            $table->string('caption')->nullable();
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
        Schema::dropIfExists('dispatch_order_files');
    }
};
