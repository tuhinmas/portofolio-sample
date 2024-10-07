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
        Schema::create('sub_dealer_data_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->uuid("personel_id");

            $table->uuid("sub_dealer_change_history_id");

            $table->uuid("sub_dealer_id");
            $table->string('name');

            $table->uuid('entity_id');

            $table->string('prefix')->nullable();

            $table->string('sufix')->nullable();

            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            $table->text('gmaps_link')->nullable();
            $table->string('owner')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('owner_ktp')->nullable();
            $table->string('owner_npwp')->nullable();
            $table->string('owner_telephone')->nullable();


            $table->foreign("sub_dealer_id")
                ->references("id")
                ->on("sub_dealers");

            $table->foreign("sub_dealer_change_history_id")
                ->references("id")
                ->on("sub_dealer_change_histories");

            $table->foreign('entity_id')
                ->references('id')
                ->on('entities')
                ->onDelete('cascade');

            $table->foreign("personel_id")
                ->references("id")
                ->on("personels");


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sub_dealer_data_histories');
    }
};
