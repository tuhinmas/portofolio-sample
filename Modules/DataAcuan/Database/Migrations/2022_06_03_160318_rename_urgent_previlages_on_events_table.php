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
        if (Schema::hasTable('event_types')) {
            Schema::table('event_types', function (Blueprint $table) {
                $table->renameColumn("urgent_previlages", "urgent_previlage");
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('event_types')) {
            Schema::table('event_types', function (Blueprint $table) {
                $table->renameColumn("urgent_previlages", "urgent_previlage");
            });
        }
    }
};
