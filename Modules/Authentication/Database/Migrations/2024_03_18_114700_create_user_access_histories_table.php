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
        Schema::create('user_access_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid("user_id");
            $table->string("device_id")->nullable();
            $table->string("latitude")->nullable();
            $table->string("longitude")->nullable();
            $table->text("gmaps_link")->nullable();
            $table->string("manufacture")->nullable();
            $table->string("model")->nullable();
            $table->string("version_app")->nullable();
            $table->string("version_os")->nullable();
            $table->boolean("is_mobile")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("user_id")
                ->references("id")
                ->on("users");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_access_histories');
    }
};
