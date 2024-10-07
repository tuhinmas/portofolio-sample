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
        Schema::table('sub_dealer_data_histories', function (Blueprint $table) {
            $table->after("telephone", function($table){
                $table->string("second_telephone")->nullable();
                $table->string("latitude")->nullable();
                $table->string("longitude")->nullable();
                $table->string("note")->nullable();
                $table->string("closed_at")->nullable();
                $table->string("closed_by")->nullable();
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
        Schema::table('sub_dealer_data_histories', function (Blueprint $table) {
            $table->dropColumn('second_telephone');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('note');
            $table->dropColumn('closed_at');
            $table->dropColumn('closed_by');
        });
    }
};
