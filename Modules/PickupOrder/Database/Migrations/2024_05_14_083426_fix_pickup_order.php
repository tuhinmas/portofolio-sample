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
        Schema::table('pickup_orders', function (Blueprint $table) {
            $table->dropColumn("status");
        });

        Schema::table('pickup_orders', function (Blueprint $table) {
            $table->enum("type_driver", ["internal","external","package"])->nullable();
            $table->enum("status", ["planned","loaded","delivered", "canceled","revised"])->nullable();
            $table->uuid("created_by")->nullable();
            $table->foreign("created_by")
                ->references("id")
                ->on("personels")
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
        Schema::table('pickup_orders', function (Blueprint $table) {
            $table->dropColumn("type_driver", "created_by");
            $table->integer("status")->change();
        });
    }
};
