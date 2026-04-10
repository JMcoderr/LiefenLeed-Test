<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaticEvents extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production') && (DB::table('event_costs')->exists() || DB::table('events')->exists())) {
            throw new \Exception('Database already seeded. Not allowed to reseed in production.');
        }

        if (DB::table('events')->count() === 0) {
            // Look into force setting the id field to 1 and increment (even overriding existing ones)
            DB::table('events')->insert([
                ['id' => 1, 'title' => '30e verjaardag'],
                ['id' => 2, 'title' => '40e verjaardag'],
                ['id' => 3, 'title' => '50e verjaardag'],
                ['id' => 4, 'title' => '60e verjaardag'],
                ['id' => 5, 'title' => '65e verjaardag'],
                ['id' => 6, 'title' => '12,5 jaar ambtenaar'],
                ['id' => 7, 'title' => '25 jaar ambtenaar'],
                ['id' => 8, 'title' => '40 jaar ambtenaar'],
                ['id' => 9, 'title' => 'ontslag/pensionering'],
                ['id' => 10, 'title' => 'huwelijk/geregistreerd partnership'],
                ['id' => 11, 'title' => '12,5 jaar huwelijk'],
                ['id' => 12, 'title' => '25 jaar huwelijk'],
                ['id' => 13, 'title' => '40 jaar huwelijk'],
                ['id' => 14, 'title' => 'gezinsuitbreiding'],
                ['id' => 15, 'title' => 'ziek'],
                ['id' => 16, 'title' => 'ziekenhuisopname'],
                ['id' => 17, 'title' => 'overlijden ambtenaar of huisgenoot'],
            ]);
        }

        if (DB::table('event_costs')->count() === 0) {
            DB::table('event_costs')->insert([
                ['id' => 1, 'event_id' => 1, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 2, 'event_id' => 2, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 3, 'event_id' => 3, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 4, 'event_id' => 4, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 5, 'event_id' => 5, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 6, 'event_id' => 6, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 7, 'event_id' => 7, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 8, 'event_id' => 8, 'amount' => 40, 'start_date' => Carbon::now()],
                ['id' => 9, 'event_id' => 9, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 10, 'event_id' => 10, 'amount' => 40, 'start_date' => Carbon::now()],
                ['id' => 11, 'event_id' => 11, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 12, 'event_id' => 12, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 13, 'event_id' => 13, 'amount' => 40, 'start_date' => Carbon::now()],
                ['id' => 14, 'event_id' => 14, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 15, 'event_id' => 15, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 16, 'event_id' => 16, 'amount' => 25, 'start_date' => Carbon::now()],
                ['id' => 17, 'event_id' => 17, 'amount' => 50, 'start_date' => Carbon::now()],
            ]);
        }
    }
}
