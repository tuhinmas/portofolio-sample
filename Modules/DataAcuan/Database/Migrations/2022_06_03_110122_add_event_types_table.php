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
        Schema::create('event_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid("personel_id");
            $table->timestamps();
        });
        
        if (Schema::hasTable('event_types')) {
            Schema::table('event_types', function (Blueprint $table) {
                $table->after("personel_id", function ($table) {
                    $table->integer("order_number_event")->nullable();
                    $table->integer("minimal_participant");
                    $table->string("code_event")->nullable();
                    $table->string("description_event")->nullable();
                    $table->integer("max_process");
                    $table->enum("urgent_previlages", ["yes", "no"]);
                    $table->integer("urgent_day_max");
                });
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
                $table->dropColumn('order_number_event');
                $table->dropColumn("minimal_participant");
                $table->dropColumn('code_event');
                $table->dropColumn('description_event');
                $table->dropColumn('max_process');
                $table->dropColumn("urgent_previlages");
                $table->dropColumn("urgent_day_max");
            });
        }
    }
};
