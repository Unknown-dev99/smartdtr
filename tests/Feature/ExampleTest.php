<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_shows_portal_home(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('SMARTDTR');
    }
}
