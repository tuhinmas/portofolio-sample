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
        Schema::create('proforma_receipts', function (Blueprint $table) {
            $table->id();
            $table->string("siup")->nullable();
            $table->string("npwp")->nullable();
            $table->string("tdp")->nullable();
            $table->string("ho")->nullable();
            $table->text("payment_info")->nullable();
            $table->uuid("confirmed_by")->nullable();
            $table->string("company_name")->default("CV. JAVAMAS AGROPHOS");
            $table->text("company_address")->nullable();
            $table->string("company_telephone")->nullable();
            $table->string("company_hp")->nullable();
            $table->string("company_email")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("confirmed_by")
                  ->references("id")
                  ->on("personels");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proforma_receipts');
    }
};
