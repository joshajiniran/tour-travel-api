<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Travel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTravelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_create_travels(): void
    {
        $response = $this->postJson('/api/v1/admin/travels');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function test_unauthorized_user_cannot_create_travels(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels');

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_create_travels_successfully(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels', [
            'name' => 'Caprizona Municado',
            'description' => 'South side open view of Capricorn mountain',
            'is_public' => false,
            'number_of_days' => 5,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Caprizona Municado']);
    }

    public function test_create_travels_invalid_payload_fails_validation(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $response = $this->actingAs($user)->postJson('/api/v1/admin/travels', [
            'name' => 'Meribund Waterfall',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_travels_successfully_with_valid_data(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));
        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/admin/travels/' . $travel->id, [
            'name' => 'Meribund Waterfall Updated',
            'is_public' => 1,
            'description' => 'Description is updated with test',
            'number_of_days' => 5,
        ]);

        $response->assertStatus(200);
        $response = $this->get('/api/v1/travels');
        $response->assertJsonFragment(['name' => 'Meribund Waterfall Updated']);
    }

    public function test_update_travels_fails_validation_with_invalid_data(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));
        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/v1/admin/travels/' . $travel->id, [
            'name' => 'Meribund Waterfall Updated'
        ]);

        $response->assertStatus(422);
    }
}