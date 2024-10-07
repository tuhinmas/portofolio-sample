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
        Schema::table('devices', function (Blueprint $table) {
            $table->after("longitude", function ($table) {
                $table->string("manufacture")->nullable();
                $table->string("model")->nullable();
                $table->string("version_app")->nullable();
                $table->string("version_os")->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('manufacture');
            $table->dropColumn('model');
            $table->dropColumn('version_app');
            $table->dropColumn('version_os');
        });
    }
};
