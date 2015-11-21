<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRepoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->integer('id')->unsigned()->primary();
            $table->string('name', 100)->unique();
            $table->string('full_name');
            $table->integer('owner_id')->unsigned();
            $table->string('description');
            $table->string('url');
            $table->string('master_branch');
            $table->boolean('fork');

            $table->foreign('owner_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('repositories');
    }
}
