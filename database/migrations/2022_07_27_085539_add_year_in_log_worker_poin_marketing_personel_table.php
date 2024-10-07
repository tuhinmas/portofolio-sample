<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYearInLogWorkerPoinMarketingPersonelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_worker_point_marketing_personel', function (Blueprint $table) {
            $table->integer('year')->nullable()->after("personel_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_worker_point_marketing_personel', function (Blueprint $table) {
            $table->integer('year')->nullable()->after("personel_id");
        });
    }
}
