<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Travel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_create_tours(): void
    {
        $travel = Travel::factory()->create();
        $response = $this->postJson('/api/v1/admin/travels/' . $travel->id . '/tours');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function test_unauthorized_user_cannot_create_tours(): void
    {
        $this->seed(RoleSeeder::class);
        $travel = Travel::factory()->create();
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels/' . $travel->id . '/tours');

        $response->assertStatus(403);
    }

    public function test_only_authorized_user_can_create_tours_successfully(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));
        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels/' . $travel->id . '/tours', [
            'name' => 'Municado Extraction',
            'start_date' => now(),
            'end_date' => now()->addDays(11),
            'price' => 199.99,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Municado Extraction']);
    }

    public function test_authorized_user_invalid_payload_fails_validation(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels', [
            'name' => 'Meribund Waterfall',
            'start_date' => '2023-12-11',
            'end_date' => '2020-11-11',
            'price' => 1000
        ]);

        $response->assertStatus(422);
    }
}