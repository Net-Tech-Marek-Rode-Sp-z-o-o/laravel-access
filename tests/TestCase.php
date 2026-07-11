<?php

declare(strict_types=1);

namespace NetCode\Access\Tests;

use Illuminate\Foundation\Application;
use NetCode\Access\Laravel\AccessServiceProvider;
use NetCode\Bus\Laravel\BusServiceProvider;
use NetCode\Domain\Laravel\DomainServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @param Application $app */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            DomainServiceProvider::class,
            BusServiceProvider::class,
            AccessServiceProvider::class,
        ];
    }

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'pgsql');
        $config->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'testing'),
            'username' => env('DB_USERNAME', 'test'),
            'password' => env('DB_PASSWORD', 'test'),
            'charset' => 'utf8',
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);
    }
}
