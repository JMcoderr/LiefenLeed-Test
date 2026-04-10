<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Request::factory()->count(100)->create();
        Request::factory()->count(100)->create(['status' => RequestStatus::ACCEPTED]);
        Request::factory()->count(100)->create(['status' => RequestStatus::REJECTED]);
        Request::factory()->count(100)->create(['status' => RequestStatus::EXPORTED]);
    }
}
