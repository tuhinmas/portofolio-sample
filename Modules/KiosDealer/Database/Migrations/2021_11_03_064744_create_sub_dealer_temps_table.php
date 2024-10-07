<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubDealerTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_dealer_temps', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('personel_id')->nullable();
            $table->uuid('sub_dealer_id')->nullable();
            $table->uuid('distributor_id')->nullable();
            $table->string("prefix")->nullable();
            $table->string('name');
            $table->string("sufix")->nullable();
            $table->text('address')->nullable();
            $table->string('telephone');
            $table->enum('status', ['draft','filed', 'submission of changes', 'filed rejected', 'change rejected'])->default('draft');
            $table->enum('status_color', ['505050', 'c2c2c2', 'faa30c', 'ffba00'])->default("505050");
            $table->text('gmaps_link')->nullable();
            $table->string('owner');
            $table->string('owner_address');
            $table->string('owner_ktp');
            $table->string('owner_npwp')->nullable();
            $table->string('owner_telephone');
            $table->string('email')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->longText('note')->nullable();
            $table->uuid("handover_status")->nullable();
            $table->unsignedBigInteger("grading_id")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('personel_id')
                ->references('id')
                ->on('personels')
                ->onDelete('cascade');

            $table->foreign('sub_dealer_id')
                ->references('id')
                ->on('sub_dealers')
                ->onDelete('cascade');
           
            $table->foreign('distributor_id')
                ->references('id')
                ->on('dealers')
                ->onDelete('cascade');

            $table->foreign("handover_status")
                ->references("id")
                ->on("handovers")
                ->onUpdate("cascade");

            $table->foreign("grading_id")
                ->references("id")
                ->on("gradings");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sub_dealer_temps');
    }
}
