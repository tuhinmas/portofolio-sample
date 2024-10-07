<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEnumOnDealerTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('dealer_temps', 'status')) {
            Schema::table('dealer_temps', function (Blueprint $table) {
                $table->dropColumn('status');
                $table->dropColumn('status_color');
            });

            Schema::table('dealer_temps', function (Blueprint $table) {
                $table->after("telephone", function($table){
                    $table->enum('status', ['draft','filed', 'submission of changes', 'filed rejected', 'change rejected'])->default('draft');
                    $table->enum('status_color', ['505050','c2c2c2', 'faa30c', 'ffba00'])->default("505050");
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
        Schema::table('dealer_temps', function (Blueprint $table) {
            $table->after("telephone", function($table){
                $table->enum('status', ['draft','filed', 'submission of changes', 'filed rejected', 'change rejected'])->default('draft');
                $table->enum('status_color', ['505050','c2c2c2', 'faa30c', 'ffba00'])->default("505050");
            });  
        });
    }
}
