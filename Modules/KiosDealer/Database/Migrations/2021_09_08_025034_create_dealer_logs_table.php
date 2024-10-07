<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealerLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dealer_logs', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("user_id");
            $table->uuid("dealer_id");
            $table->string("activity");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("user_id")
                  ->references("id")
                  ->on("users")
                  ->onUpdate("cascade");
            $table->foreign("dealer_id")
                  ->references("id")
                  ->on("dealers")
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
        Schema::dropIfExists('dealer_logs');
    }
}
