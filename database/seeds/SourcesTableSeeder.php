<?php

use Illuminate\Database\Seeder;

// composer require laracasts/testdummy
use Laracasts\TestDummy\Factory as TestDummy;
use App\Source;

class SourcesTableSeeder extends Seeder
{
    public function run()
    {
        Source::create([
          'name' => 'eventbrite',
          'max_requests' => 900,
          'interval_seconds' => 86400,
        ]);

        Source::create([
          'name' => 'viagogo',
          'max_requests' => 900,
          'interval_seconds' => 86400,
        ]);
    }
}
