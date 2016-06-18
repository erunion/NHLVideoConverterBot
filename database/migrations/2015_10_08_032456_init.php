<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Init extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->string('thing_id', 50)->unique('thing_id');
            $table->string('subreddit', 255);
            $table->text('url');
            $table->timestamps();

            $table->index(['subreddit'], 'subreddit');
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->string('id', 255)->unique('id');
            $table->string('thing_id', 50);
            $table->text('url');
            $table->text('title')->nullable();
            $table->text('converted_url');
            $table->string('filesize');
            $table->string('runtime')->nullable();
            $table->timestamps();

            $table->index(['thing_id'], 'thing_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('posts');
        Schema::drop('videos');
    }
}
