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
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid("credit_invoice")->after("user_id")->nullable()->comment("mostly use in return direct sales");
            $table->foreign("credit_invoice")
                ->references("id")
                ->on("invoices")
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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['credit_invoice']);
            $table->dropColumn('credit_invoice');
        });
    }
};
