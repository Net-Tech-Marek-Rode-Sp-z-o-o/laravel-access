<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class UnguardedAdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('access.admin_permission', null);
    }

    #[Test]
    public function an_empty_admin_permission_leaves_the_routes_to_the_host(): void
    {
        $this->getJson('/access/roles')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
