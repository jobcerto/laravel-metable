<?php

namespace Jobcerto\Metable\Tests;

use Illuminate\Database\Schema\Blueprint;
use Jobcerto\Metable\MetableServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->loadLaravelMigrations(['--database' => 'sqlite']);

        $this->setUpDatabase();

    }

    protected function getPackageProviders($app)
    {
        return [
            MetableServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('app.key', 'base64:6Cu/ozj4gPtIjmXjr8EdVnGFNsdRqZfHfVjQkmTlg4Y=');
    }

    protected function setUpDatabase()
    {
        include_once __DIR__ . '/../database/migrations/create_metable_table.php.stub';

        (new \CreateMetableTable())->up();

        $this->app['db']->connection()->getSchemaBuilder()->create('tag_metas', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('subject');
            $table->string('key');
            $table->longText('value');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });
    }

}
