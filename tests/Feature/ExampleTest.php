<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_sent_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('home'));

        $response->assertRedirect('/dashboard');
    }
}
