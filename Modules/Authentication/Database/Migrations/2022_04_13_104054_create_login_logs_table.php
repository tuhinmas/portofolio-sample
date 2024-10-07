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
        Schema::create('log_logins', function (Blueprint $table) {
            $table->id();
            $table->uuid("user_id");
            $table->date("date");
            $table->string("token");
            $table->timestamp("login_at")->useCurrent();
            $table->timestamp("logout_at")->nullable();
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
        Schema::dropIfExists('log_logins');
    }
};
