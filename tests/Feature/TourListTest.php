<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourListTest extends TestCase
{
    use RefreshDatabase;

    public function test_tours_list_by_travel_slug_returns_correct_tours(): void
    {
        $travel = Travel::factory()->create();
        $tour = Tour::factory(2)->create(['travel_id' => $travel->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $tour[0]->id]);
    }

    public function test_tour_price_is_shown_correctly(): void
    {
        $travel = Travel::factory()->create();
        $tour = Tour::factory()->create(['travel_id' => $travel->id, 'price' => 199.99]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['price' => '199.99']);
    }

    public function test_tours_list_returns_pagination(): void
    {
        $toursPerPage = config('app.paginationPerPage.tours');

        $travel = Travel::factory()->create();
        $tour = Tour::factory($toursPerPage + 1)->create(['travel_id' => $travel->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours');

        $response->assertStatus(200);
        $response->assertJsonCount($toursPerPage, 'data');
        $response->assertJsonPath('meta.last_page', 2);
    }
}