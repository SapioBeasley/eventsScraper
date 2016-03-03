<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('source_id')->unsigned();
            $table->foreign('source_id')->references('id')->on('sources')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name')->nullable();
            $table->boolean('is_enabled')->index();
            $table->mediumText('url')->nullable();
            $table->timestamp('last_run_at')->index();
            $table->timestamps();

            // Indexes
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('jobs');
    }
}
