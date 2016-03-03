<?php

namespace App\Console\Commands;

use App\Job;

use App\Commands\EventbriteScraperCommand;
use App\Commands\ViagogoScraperCommand;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Console\Command;

class ScrapeRun extends Command
{
    use DispatchesCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:run {--job=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run events scraper';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Retrieve a specific parameter
        $scrapeId = $this->option('job');

        // Check the value of id
        if (! isset($scrapeId) || $scrapeId === null) {

            // Return error message
            $this->error('Id cannot be null');

            // End execution
            return;
        }

        // Find job using scrape id
        $job = Job::find($scrapeId);

        // Does the job exist?
        if ($job === null) {

            // Return error if job does not exist
            $this->error('Invalid ID used');

            // End execution
            return;

        // Check if job is enabled
        } elseif ($job->is_enabled === false) {

            // Print not enabled error
            $this->error('Specified job is not enabled');

            // End execution
            return;
        }

        // Run command based on ID provided
        switch (true) {

            // Is jobs related source name set
            case isset($job->source->name):

                // Show which job we are running
                $this->info($job->name . ' has begun using ' . $job->source->name .' scraper job ' . $scrapeId);

                // Instanciate command and run it
                $scrapeStatus = new EventbriteScraperCommand($scrapeId);

                // Run scrape
                $this->dispatch($scrapeStatus);

                // Check if status is true
                if ($scrapeStatus->results['success'] !== true) {

                    // Check if errors are in array
                    if (is_array($scrapeStatus->results['error'])) {

                        // Loop and echo errors
                        foreach ($scrapeStatus->results['error'] as $error) {

                            // return errors
                            $this->error($error);
                        }

                        // Print error
                        $this->error('Check your jobs table for missing fields');

                    } else {

                        // return error
                        $this->error($scrapeStatus->results['error']);
                    }

                    // end execution
                    return;
                }

                // Scrape complete notice
                $this->info('Scraping complete');

                // End execution
                return;

            // If all else fails
            default:

                // Print Invalid user
                $this->error('Invalid ID used');
                break;
        }
    }
}
