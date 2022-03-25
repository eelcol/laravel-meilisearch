<?php

namespace Eelcol\LaravelMeilisearch\Commands;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:create-index {index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an index for Meilisearch';

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
        $path = base_path('database/meilisearch/' . $this->argument('index') . '.php');

        if (!File::isDirectory(base_path('database/meilisearch'))) {
            File::makeDirectory(base_path('database/meilisearch'));
        }

        File::copy(
            __DIR__ . "/../../stubs/index.php",
            $path
        );

        return 0;
    }
}
