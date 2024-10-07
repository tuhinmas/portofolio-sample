<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDealersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->uuid('user_id')->nullable();
            $table->string('dealer_id');
            $table->string('name');
            $table->text('address');
            $table->string('telephone');
            $table->enum('status', ['rejected', 'accepted', 'submission of changes', 'filed'])->nullable();
            $table->enum('status_color', ['c2c2c2', 'faa30c', 'ff0000'])->default('c2c2c2');
            $table->text('gmaps_link')->nullable();
            $table->string('owner');
            $table->string('owner_address');
            $table->string('owner_ktp');
            $table->string('owner_npwp');
            $table->string('owner_telephone');
            $table->uuid('agency_level_id');
            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('agency_level_id')
                ->references('id')
                ->on('agency_levels')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dealers');
    }
}
