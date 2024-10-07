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
        Schema::create('log_porters', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");


            $table->uuid('warehouse_id')->nullable();
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses');

            $table->uuid('porter_id')->nullable();
            $table->foreign('porter_id')
                ->references('id')
                ->on('porters');

            $table->uuid('personel_id')->nullable();
            $table->foreign('personel_id')
                ->references('id')
                ->on('personels');

            $table->string('status')->nullable();
                
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_porters');
    }
};
