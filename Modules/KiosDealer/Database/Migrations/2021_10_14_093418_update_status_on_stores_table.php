<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusOnStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_temps', function (Blueprint $table) {
            Schema::table('store_temps', function (Blueprint $table) {
                if (Schema::hasColumn('store_temps', 'status_color')) {
                    $table->dropColumn('status_color');
                }
            });

            Schema::table('store_temps', function (Blueprint $table) {
                if (Schema::hasColumn('store_temps', 'status')) {
                    $table->dropColumn('status');
                }
            });
            Schema::table('store_temps', function (Blueprint $table) {
                $table->after('gmaps_link', function ($table) {
                    $table->enum('status', ['filed', 'submission of changes', 'filed rejected', 'change rejected'])->default('filed');
                    $table->enum('status_color', ['c2c2c2', 'faa30c', 'ffba00'])->default("c2c2c2");
                });
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
        Schema::table('store_temps', function (Blueprint $table) {
        });
    }
}
