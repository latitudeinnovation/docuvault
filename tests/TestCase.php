<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Hard guard: tests must NEVER run against the dev/prod database.
     *
     * This container injects the real .env (DB_CONNECTION=mysql) as a process
     * environment variable via docker-compose `env_file`, which overrides the
     * sqlite settings in phpunit.xml. Without this guard, RefreshDatabase would
     * run `migrate:fresh` against the live MySQL database and wipe it.
     *
     * Forcing the connection here — after the application boots but before the
     * RefreshDatabase trait runs in setUpTraits() — guarantees an isolated
     * in-memory database no matter how the suite is invoked.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');
    }
}
