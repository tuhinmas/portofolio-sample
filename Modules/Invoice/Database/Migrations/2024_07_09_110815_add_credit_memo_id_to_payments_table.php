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
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid("credit_memo_id")->after("is_credit_memo")->nullable();
            $table->foreign("credit_memo_id")
                ->references("id")
                ->on("credit_memos")
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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(["credit_memo_id"]);
            $table->dropColumn("credit_memo_id");
        });
    }
};
