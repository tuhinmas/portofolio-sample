<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealerTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dealer_temps', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('personel_id')->nullable();
            $table->string('dealer_id')->nullable();
            $table->string("prefix")->nullable();
            $table->string('name');
            $table->string("sufix")->nullable();
            $table->text('address')->nullable();
            $table->string('telephone');
            $table->enum('status', ['filed', 'submission of changes', 'filed rejected', 'change rejected'])->default('filed');
            $table->enum('status_color', ['c2c2c2', 'faa30c', 'ffba00'])->default("c2c2c2");
            $table->text('gmaps_link')->nullable();
            $table->string('owner');
            $table->string('owner_address');
            $table->string('owner_ktp');
            $table->string('owner_npwp')->nullable();
            $table->string('owner_telephone');
            $table->string('email')->nullable();
            $table->uuid('agency_level_id')->nullable();
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

            $table->foreign('agency_level_id')
                ->references('id')
                ->on('agency_levels')
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
        Schema::dropIfExists('dealer_temps');
    }
}
