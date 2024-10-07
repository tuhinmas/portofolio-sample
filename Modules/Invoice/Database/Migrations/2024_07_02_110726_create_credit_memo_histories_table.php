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
        Schema::create('credit_memo_histories', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("personel_id");
            $table->uuid("credit_memo_id");
            $table->string("status");
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("personel_id")
                ->references("id")
                ->on("personels")
                ->onUpdate("cascade");

            $table->foreign("credit_memo_id")
                ->references("id")
                ->on("credit_memos")
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
        Schema::dropIfExists('credit_memo_histories');
    }
};
