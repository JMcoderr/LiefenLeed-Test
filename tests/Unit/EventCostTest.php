<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_event_cost(): void
    {
        $event = Event::factory()->create();

        $date = now()->subDays(2);

        $cost = EventCost::create([
            'event_id' => $event->id,
            'start_date' => $date,
            'amount' => 10.5,
        ]);

        $this->assertInstanceOf(EventCost::class, $cost);
        $this->assertEquals($event->id, $cost->event_id);
        $this->assertEquals($cost->start_date, $date);
    }
}
