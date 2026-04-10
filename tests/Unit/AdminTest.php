<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\EventCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_admin(): void
    {
        $email = 'example@almere.nl';
        $admin = Admin::factory()->create([
            'employee' => 'example@almere.nl',
        ]);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertEquals($email, $admin->employee);
        $this->assertNull($admin->super);
    }

    public function test_can_create_super(): void
    {
        $admin = Admin::factory()->create([
            'super' => now()
        ]);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertTrue($admin->super);
    }

    public function test_can_delete_admin(): void
    {
        $admin = Admin::factory()->create();

        $admin->delete();

        $this->assertDatabaseMissing('admins', $admin->toArray());
    }
}
