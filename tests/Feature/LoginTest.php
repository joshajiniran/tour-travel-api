<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_successful_with_correct_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token']);
    }

    public function test_login_unsuccessful_with_incorrect_credentials(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistingeuser@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'The provided credentials are incorrect.']);
    }
}
