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

    public function test_tours_list_sorts_by_start_date_correctly(): void
    {
        $travel = Travel::factory()->create();
        $laterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
        ]);
        $earlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'start_date' => now(),
            'end_date' => now()->addDays(1),
        ]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $earlierTour->id);
        $response->assertJsonPath('data.1.id', $laterTour->id);
    }

    public function test_tours_list_sorts_by_price_correctly(): void
    {
        $travel = Travel::factory()->create();
        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 200,
        ]);
        $cheapLaterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
        ]);
        $cheapEarlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'start_date' => now(),
            'end_date' => now()->addDays(1),
        ]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?sortBy=price&sortOrder=desc');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $expensiveTour->id);
        $response->assertJsonPath('data.1.id', $cheapEarlierTour->id);
        $response->assertJsonPath('data.2.id', $cheapLaterTour->id);
    }

    public function test_tours_list_filters_by_price_correctly(): void
    {
        $travel = Travel::factory()->create();
        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 200,
        ]);
        $cheapTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
        ]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?priceFrom=100');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?priceFrom=150');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $expensiveTour->id]);
        $response->assertJsonMissing(['id' => $cheapTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?priceFrom=250');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonMissing(['id' => $expensiveTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?priceTo=200');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?priceTo=150');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonMissing(['id' => $expensiveTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?priceTo=50');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonMissing(['id' => $expensiveTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?priceFrom=150&priceTo=250');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $expensiveTour->id]);
        $response->assertJsonMissing(['id' => $cheapTour->id]);
    }

    public function test_tours_list_filters_by_date_correctly(): void
    {
        $travel = Travel::factory()->create();
        $earlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2)
        ]);
        $laterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5)
        ]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateFrom=' . now());

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateFrom=' . now()->addDays(1));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateFrom=' . now()->addDays(4));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonMissing(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateTo=' . now()->addDays(5));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateTo=' . now()->addDays(2));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonMissing(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateTo=' . now()->addDays(1));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonMissing(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateFrom=' . now() . '&dateTo=' . now()->addDays(6));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateFrom=' . now()->subDays(1) . '&dateTo=' . now()->addDays(2));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonMissing(['id' => $laterTour->id]);

        $response = $this->get('/api/v1/travels/' . $travel->slug . '/tours?dateFrom=' . now()->subDays(7) . '&dateTo=' . now()->subDays(5));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonMissing(['id' => $laterTour->id]);
    }

    public function test_tours_list_returns_validation_errors(): void
    {
        $travel = Travel::factory()->create();

        $response = $this->getJson('/api/v1/travels/' . $travel->slug . '/tours?dateFrom=abcde');
        $response->assertStatus(422);

        $response = $this->getJson('/api/v1/travels/' . $travel->slug . '/tours?priceFrom=abcde');
        $response->assertStatus(422);
    }    

}