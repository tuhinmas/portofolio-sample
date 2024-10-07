<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonelNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personel_notes', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("user_id");
            $table->uuid("personel_id");
            $table->mediumText("note")->nullable();
            $table->enum("type", ["1", "2", "3", "4", "5"])->comment("1 => sp, 2 => warning, 3 => other, 4 => coming soon, 5 => coming soon")->nullable();
            $table->string("status");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("user_id")
                ->references("id")
                ->on("users")
                ->onDelete("cascade");

            $table->foreign("personel_id")
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
        Schema::dropIfExists('personel_notes');
    }
}
