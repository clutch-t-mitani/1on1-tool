<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_SPAシェルが表示される(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_任意のパスでSPAシェルが表示される(): void
    {
        $this->withoutVite();

        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
