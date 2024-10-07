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
        Schema::create('notification_group_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("notification_group_id");
            $table->string("model");
            $table->string("task_text");
            $table->json("condition");
            $table->integer("task_count");
            $table->string("mobile_link")->nullable();
            $table->string("desktop_link")->nullable();
            $table->boolean("status_running")->defaul(0);
            $table->timestamp("last_check")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("notification_group_id")
                ->references("id")
                ->on("notification_groups")
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
        Schema::dropIfExists('notification_group_details');
    }
};
