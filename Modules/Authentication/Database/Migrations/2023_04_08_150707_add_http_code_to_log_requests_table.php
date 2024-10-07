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
        Schema::table('log_requests', function (Blueprint $table) {
            $table->after("route", function ($table) {
                $table->string("http_code")->nullable();
                $table->text("user_agent")->nullable();
            });

            $table->uuid("user_id")->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_requests', function (Blueprint $table) {
            $table->dropColumn('http_code');
            $table->dropColumn('user_agent');
        });
    }
};
