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
        Schema::create('dealer_delivery_addresses', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->string("name")->nullable();
            $table->uuid("dealer_id");
            $table->string("address")->nullable();
            $table->char("province_id")->nullable();
            $table->char("city_id")->nullable();
            $table->char("district_id")->nullable();
            $table->string("postal_code")->nullable();
            $table->string("telephone")->nullable();
            $table->string("latitude")->nullable();
            $table->string("longitude")->nullable();
            $table->text("gmaps_link")->nullable();
            $table->boolean("is_active")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers")
                ->onDelete("cascade");
                
            $table->foreign("province_id")
                ->references("id")
                ->on("indonesia_provinces")
                ->onDelete("cascade");

            $table->foreign("city_id")
                ->references("id")
                ->on("indonesia_cities")
                ->onDelete("cascade");

            $table->foreign("district_id")
                ->references("id")
                ->on("indonesia_districts")
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
        Schema::dropIfExists('dealer_delivery_addresses');
    }
};
