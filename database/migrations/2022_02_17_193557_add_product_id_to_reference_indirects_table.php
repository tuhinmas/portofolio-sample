<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductIdToReferenceIndirectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reference_indirects', function (Blueprint $table) {
            $table->uuid('product_id')->nullable();
            $table->double('unit_price')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reference_indirects', function (Blueprint $table) {
            $table->dropColumn('unit_price');
            $table->dropColumn('product_id');

        });
    }
}
