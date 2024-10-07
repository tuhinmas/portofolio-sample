<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteParentIdFromMarketingAreaRegions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('marketing_area_regions', 'name')) {
            Schema::table('marketing_area_regions', function (Blueprint $table) {
                $table->after("id", function($table){
                    $table->string('name');
                });
            }); 
        }

        if (Schema::hasColumn('marketing_area_regions', 'parent_id')) {
            Schema::table('marketing_area_regions', function (Blueprint $table) {
                $table->dropColumn('parent_id');
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
        Schema::table('marketing_area_regions', function (Blueprint $table) {

        });
    }
}
