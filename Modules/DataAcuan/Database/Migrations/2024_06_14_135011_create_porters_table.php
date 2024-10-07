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
        Schema::create('porters', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");

            $table->uuid("personel_id");
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels");

            $table->uuid('warehouse_id');
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses');

            $table->uuid('updated_by')->nullable();
            $table->foreign('updated_by')
                ->references('id')
                ->on('personels');

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
        Schema::dropIfExists('porters');
    }
};
