<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneNumberReferenceToStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->after("sub_dealer_id", function ($table) {
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
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['phone_number_reference']);
            $table->dropColumn('phone_number_reference');
        });
    }
}
