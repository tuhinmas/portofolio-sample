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
        Schema::table('dealer_data_histories', function (Blueprint $table) {
            $table->string("second_telephone")->after("telephone")->nullable();
        });

        Schema::table('dealer_data_histories', function (Blueprint $table) {
            $table->after("second_telephone", function ($table) {
                $table->string("is_distributor")->nullable();
                $table->string("latitude")->nullable();
                $table->string("longitude")->nullable();
            });
        });

        Schema::table('dealer_data_histories', function (Blueprint $table) {
            $table->after("bank_account_number", function ($table) {
                $table->string("bank_account_name")->nullable();
                $table->timestamp("closed_at")->nullable();
                $table->uuid("closed_by")->nullable();
                $table->timestamp("blocked_at")->nullable();
                $table->uuid("blocked_by")->nullable();
                $table->string("note")->nullable();

                $table->uuid("bank_id")->nullable();
                $table->foreign("bank_id")
                    ->references("id")
                    ->on("banks")
                    ->onUpdate("cascade");

                $table->foreign("closed_by")
                    ->references("id")
                    ->on("personels")
                    ->onUpdate("cascade");

                $table->foreign("blocked_by")
                    ->references("id")
                    ->on("personels")
                    ->onUpdate("cascade");
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
        Schema::table('dealer_data_histories', function (Blueprint $table) {
            $table->dropColumn('second_telephone');
            $table->dropColumn('is_distributor');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('bank_account_name');
            $table->dropColumn('closed_at');
            $table->dropColumn('blocked_at');
            $table->dropColumn('note');
            
            $table->dropForeign(["closed_by"]);
            $table->dropForeign(["blocked_by"]);
            $table->dropForeign(["bank_id"]);
            $table->dropColumn('closed_by');
            $table->dropColumn('blocked_by');
            $table->dropColumn('bank_id');
        });
    }
};
