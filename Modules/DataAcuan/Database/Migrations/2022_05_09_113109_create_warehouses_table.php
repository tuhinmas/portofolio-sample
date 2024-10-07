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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string("code");
            $table->string("name");
            $table->string("address");
            $table->string("province_id");
            $table->foreign("province_id")
                ->references("id")
                ->on("indonesia_provinces");
            $table->string("city_id");
            $table->foreign("city_id")
                ->references("id")
                ->on("indonesia_cities");
            $table->string("district_id");
            $table->foreign("district_id")
                ->references("id")
                ->on("indonesia_districts");
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
        Schema::dropIfExists('warehouses');
    }
};
