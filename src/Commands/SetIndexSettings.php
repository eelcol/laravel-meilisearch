<?php

namespace Eelcol\LaravelMeilisearch\Commands;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetIndexSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:set-index-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set index settings for Meilisearch';

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
     * @return int
     */
    public function handle()
    {
        $files = File::allFiles(base_path('database/meilisearch'));

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $index = str_replace("." . $file->getExtension(), "", $filename);

            // require the file
            $config = File::getRequire($file->getRealPath());

            // first create the index if it does not exist yet
            if (!Meilisearch::indexExists($index)) {
                $task = Meilisearch::createIndex($index, $config['primaryKey']);

                // now wait till the task is finished
                $task->checkStatus();
                while ($task->isNotSucceeded()) {
                    if ($task->isFailed()) {
                        trigger_error("Failed creating index!");
                    }

                    // wait 1 second
                    sleep(1);
                    $task->checkStatus();
                }

                dump("Index '" . $index . "' created...");
            }

            // sync filterable, searchable and sortable attributes
            Meilisearch::syncFilterableAttributes($index, ($config['filters'] ?? []));
            Meilisearch::syncSearchableAttributes($index, ($config['search'] ?? []));
            Meilisearch::syncSortableAttributes($index, ($config['sortable'] ?? []));
        }

        return 0;
    }
}
