<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneNumberReferenceToStoreTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_temps', function (Blueprint $table) {
            $table->after("change_note", function ($table) {
                $table->uuid("phone_number_reference")->nullable();
                $table->foreign("phone_number_reference")
                    ->references("id")
                    ->on("stores")
                    ->onDelete("cascade");
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
            $table->dropForeign(['phone_number_reference']);
            $table->dropColumn('phone_number_reference');
        });
    }
}
