<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Models\Admin;
use App\Models\Event;
use App\Models\EventCost;
use App\Models\Request;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
        $this->call(StaticEvents::class);

//        Admin::factory()->create(['employee' => 'admin@almere.nl']);
//        Event::factory()->count(10)->has(EventCost::factory())->create();
//        EventCost::factory(10)->has(Event::factory())->create();

    }
}
