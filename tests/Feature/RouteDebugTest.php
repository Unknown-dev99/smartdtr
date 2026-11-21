<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteDebugTest extends TestCase
{
    public function test_root_route_is_registered(): void
    {
        $this->assertTrue(Route::has('login'));

        $response = $this->get('/login');
        $response->assertOk();

        $response = $this->get('/');
        if ($response->status() === 404) {
            $this->fail('Root returned 404. Registered URIs: '.implode(', ', collect(Route::getRoutes())->map(fn ($r) => $r->uri())->take(10)->all()));
        }

        $response->assertRedirect(route('login'));
    }
}
