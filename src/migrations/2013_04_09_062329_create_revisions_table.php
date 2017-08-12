<?php

use Illuminate\Database\Migrations\Migration;

class CreateRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revision', function ($table) {
            $table->increments('id');
            $table->string('revisionableType');
            $table->integer('revisionableId');
            $table->integer('userId')->nullable();
            $table->string('key');
            $table->text('oldValue')->nullable();
            $table->text('newValue')->nullable();
            $table->timestamps();

            $table->index(array('revisionableId', 'revisionableType'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('revision');
    }
}
