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
       
            Schema::table('budget_provinces', function (Blueprint $table) {   
                $table->dropPrimary();
                $table->dropColumn("id");
            });

            
            Schema::table('budget_provinces', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->first();
            });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('budget_provinces', function (Blueprint $table) {
            $table->uuid('id')->unique();
        });
    }
};
