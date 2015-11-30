<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContributesToTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contributes_to', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('repository_id')->unsigned();
            $table->integer('contributions');

            $table->primary(['user_id', 'repository_id']);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('repository_id')->references('id')->on('repositories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('contributes_to');
    }
}
