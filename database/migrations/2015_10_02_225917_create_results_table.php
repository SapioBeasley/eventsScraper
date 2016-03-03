<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('results', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('job_id')->unsigned();
            $table->string('identifier')->nullable()->index();
            $table->timestamp('start_date')->nullable()->index();
            $table->timestamp('end_date')->nullable()->index();
            $table->string('timezone')->nullable()->index();
            $table->timestamp('processed_at')->index()->nullable();
            $table->string('processed_status')->nullable()->index();
            $table->mediumText('result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('results');
    }
}
