<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class DisabledAdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('access.routes', false);
    }

    #[Test]
    public function the_package_registers_no_routes_when_they_are_disabled(): void
    {
        $this->getJson('/access/roles')->assertNotFound();
    }
}
