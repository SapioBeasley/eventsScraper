<?php

use Illuminate\Database\Seeder;

// composer require laracasts/testdummy
use Laracasts\TestDummy\Factory as TestDummy;
use App\Job;

class JobsTableSeeder extends Seeder
{
    public function run()
    {
        Job::create([
          'source_id' => 1,
          'name' => 'Scrape Las Vegas',
          'is_enabled' => true,
          'url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=vegas&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&start_date.range_start={start_date}&start_date.range_end={end_date}&token={token}',
        ]);

        Job::create([
          'source_id' => 1,
          'name' => 'Scrape Los Angeles',
          'is_enabled' => true,
          'url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=los%20angeles&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&start_date.range_start={start_date}&start_date.range_end={end_date}&token={token}',
        ]);

        Job::create([
          'source_id' => 1,
          'name' => 'Scrape New York',
          'is_enabled' => true,
          'url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=new%20york&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&start_date.range_start={start_date}&start_date.range_end={end_date}&token={token}',
        ]);

        Job::create([
          'source_id' => 1,
          'name' => 'Scrape Florida',
          'is_enabled' => true,
          'url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=florida&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&start_date.range_start={start_date}&start_date.range_end={end_date}&token={token}',
        ]);

        Job::create([
          'source_id' => 1,
          'name' => 'Scrape Arizona',
          'is_enabled' => true,
          'url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=arizona&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&start_date.range_start={start_date}&start_date.range_end={end_date}&token={token}',
        ]);

        Job::create([
          'source_id' => 1,
          'name' => 'Scrape Utah',
          'is_enabled' => true,
          'url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=utah&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&start_date.range_start={start_date}&start_date.range_end={end_date}&token={token}',
        ]);

        Job::create([
          'source_id' => 1,
          'name' => 'Scrape Global',
          'is_enabled' => true,
          'url' => 'https://www.eventbriteapi.com/v3/events/search/?expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&start_date.range_start={start_date}&start_date.range_end={end_date}&token={token}',
        ]);

        Job::create([
          'source_id' => 2,
          'name' => 'Scrape everything',
          'is_enabled' => false,
          'url' => 'http://api-here',
        ]);
    }
}
