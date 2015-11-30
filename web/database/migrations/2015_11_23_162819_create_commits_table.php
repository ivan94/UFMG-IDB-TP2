<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commits', function (Blueprint $table) {
            $table->string('sha', 100)->primary();
            $table->string('message');
            $table->string('url');
            $table->integer('user_id')->unsigned();
            $table->integer('repository_id')->unsigned();
            $table->string('branch_name', 100);

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign(['repository_id', 'branch_name'])->references(['repository_id', 'name'])->on('branches');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('commits');
    }
}
