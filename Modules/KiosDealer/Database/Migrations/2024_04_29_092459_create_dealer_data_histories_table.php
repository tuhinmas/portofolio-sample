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
        Schema::create('dealer_data_histories', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->uuid("personel_id");

            $table->uuid("dealer_change_history_id");

            $table->uuid("dealer_id");
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

            $table->string("bank_account_number")->nullable();
            $table->string("bank_name")->nullable();

            $table->string("owner_bank_account_number")->nullable();
            $table->string("owner_bank_account_name")->nullable();
            $table->string("owner_bank_name")->nullable();

            $table->uuid("owner_bank_id")->nullable();

            $table->foreign("dealer_id")
                ->references("id")
                ->on("dealers");

            $table->foreign("dealer_change_history_id")
                ->references("id")
                ->on("dealer_change_histories");

            $table->foreign('entity_id')
                ->references('id')
                ->on('entities')
                ->onDelete('cascade');

            $table->foreign("personel_id")
                ->references("id")
                ->on("personels");

            $table->foreign("owner_bank_id")
                    ->references("id")
                    ->on("banks")
                    ->onDelete("cascade");


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
        Schema::dropIfExists('dealer_data_histories');
    }
};
