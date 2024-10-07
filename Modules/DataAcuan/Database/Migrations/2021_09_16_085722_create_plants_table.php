<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->unsignedBigInteger("plant_category_id");
            $table->string("name");
            $table->string("varieties")->nullable();
            $table->string("scientific_name")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("plant_category_id")
                  ->references("id")
                  ->on("plant_categories")
                  ->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plants');
    }
}
