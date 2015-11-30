<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePullsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pulls', function (Blueprint $table) {
            $table->integer('number');
            $table->integer('repository_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->text('title');
            $table->text('state');
            $table->boolean('locked');
            $table->string('url');

            $table->primary(['number', 'repository_id']);
            $table->foreign('repository_id')->references('id')->on('repositories');
            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pulls');
    }
}
