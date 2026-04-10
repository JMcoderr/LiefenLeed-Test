<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function get_admin_magic(): array
    {
        return [
            'id' => 1,
            'email' => 'admin@almere.nl',
            'expires_at' => now()->addDay(),
            'admin' => [
                'isAdmin' => true,
                'isSuper' => true
            ]
        ];
    }

    public function test_events_screen_can_be_rendered(): void
    {
        $admin = $this->get_admin_magic();

        $response = $this->withSession(['magic' => $admin])
            ->get(route('admin.events.index'));

        $response->assertStatus(200)->assertInertia(fn($page) =>
                $page->component('admin/Events')
            );
    }

    public function test_events_screen_can_be_rendered_with_their_data(): void
    {
        Event::factory()->create([
            'title' => 'Event 1',
        ]);

        $admin = $this->get_admin_magic();

        $response = $this->withSession(['magic' => $admin])
            ->get(route('admin.events.index'));

        $response->assertStatus(200)
            ->assertInertia(fn($page) =>
                $page->component('admin/Events')
                    ->has('events', 18)
                    ->has('events.0.id')
                    ->has('events.0.title')
                    ->has('events.0.current_cost')
            );
    }
}
