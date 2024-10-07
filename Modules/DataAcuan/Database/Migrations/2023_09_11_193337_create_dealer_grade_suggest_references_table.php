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
        Schema::create('dealer_grade_suggestions', function (Blueprint $table) {
            $table->uuid("id");
            $table->primary("id");
            $table->unsignedBigInteger("grading_id");
            $table->uuid("payment_method_id");
            $table->integer("maximum_settle_days")->default(0);
            $table->double("proforma_last_minimum_amount", 20,2)->default(0)->comment("minimum total proforma terakhir");
            $table->integer("proforma_sequential")->default(0)->comment("jumlah urutan proforma");
            $table->double("proforma_total_amount", 20,2)->default(0)->comment("jumlah akumulasi proforma");
            $table->integer("proforma_count")->default(0)->comment("jumlah proforma untuk akumulasi");
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("grading_id")
                ->references("id")
                ->on("gradings")
                ->onDelete("cascade");

            $table->foreign("payment_method_id")
                ->references("id")
                ->on("payment_methods")
                ->onDelete("cascade");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dealer_grade_suggestions');
    }
};
