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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string("notified_feature", 225)->nullable();
            $table->string("notification_text", 225)->nullable();
            $table->string("mobile_link", 225)->nullable();
            $table->string("desktop_link", 225)->nullable();
            $table->uuid("data_id")->nullable();
            $table->boolean("as_marketing")->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('notified_feature');
            $table->dropColumn('notification_text');
            $table->dropColumn('mobile_link');
            $table->dropColumn('desktop_link');
            $table->dropColumn('data_id');
            $table->dropColumn('as_marketing');
        });
    }
};
