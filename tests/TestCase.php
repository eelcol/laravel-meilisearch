<?php

namespace Eelcol\LaravelMeilisearch\Tests;

use Eelcol\LaravelMeilisearch\LaravelMeilisearchServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // additional setup
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelMeilisearchServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        //$this->loadMigrationsFrom(__DIR__ . '/database/migrations');

//        $this->artisan('migrate', [
//            //'--database' => 'testbench',
//            '--realpath' => realpath(__DIR__.'/../tests/database/migrations'),
//        ])->run();
//
//        $this->beforeApplicationDestroyed(function () {
//            $this->artisan('migrate:rollback', [
//                //'--database' => 'testbench',
//                '--realpath' => realpath(__DIR__.'/../tests/database/migrations')
//            ])->run();
//        });
    }
}