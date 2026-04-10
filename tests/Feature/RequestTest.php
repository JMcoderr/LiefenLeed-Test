<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Member;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    private function get_employee_magic(): array
    {
        return [
            'email' => 'icttest@almere.nl',
            'expires_at' => now()->addDay(),
            'admin' => [
                'isAdmin' => false,
                'isSuper' => false
            ]
        ];
    }

    private function get_admin_magic(): array
    {
        return [
            'email' => 'admin@almere.nl',
            'expires_at' => now()->addDay(),
            'admin' => [
                'isAdmin' => true,
                'isSuper' => true
            ]
        ];
    }

    public function test_request_screen_can_be_rendered(): void
    {
        $magic = $this->get_employee_magic();

        $response = $this->withSession(['magic' => $magic])->get(route('requests'));

        $response->assertStatus(200)->assertInertia(fn ($page) => $page->component('Request'));
    }

    public function test_request_can_be_made(): void
    {
        Member::factory()->create([
            'email' => 'zzijpalm@almere.nl'
        ]);
        $magic = $this->get_employee_magic();

        $response = $this->withSession(['magic' => $magic])->post(route('requests.store'), [
            'employee_requester' => $magic['email'],
            'employee_recipient' => 'zzijpalm@almere.nl',
            'event_cost_id' => 10,
            'account_name' => 'test',
            'iban' => 'NL90ABNA0123456789'
        ]);

        $response->assertStatus(302)->assertSessionHas('toast', ['type' => 'success', 'message' => 'Aanvraag succesvol ingediend.']);

        $this->assertDatabaseHas('requests', [
            'event_cost_id' => 10,
            'account_name' => 'test',
        ]);
    }

    public function test_request_cannot_be_made_with_invalid_recipient(): void
    {
        $magic = $this->get_employee_magic();

        $response = $this->withSession(['magic' => $magic])->post(route('requests.store'), [
            'employee_requester' => $magic['email'],
            'employee_recipient' => 'test@almere.nl',
            'event_cost_id' => 10,
            'account_name' => 'test',
            'iban' => 'NL90ABNA0123456789'
        ]);

        $response->assertStatus(302)->assertSessionHasErrors([
            'employee_recipient' => 'De opgegeven ontvanger kon niet worden gevonden.'
        ]);
    }

    public function test_request_cannot_be_made_with_same_email(): void
    {
        $magic = $this->get_employee_magic();

        $response = $this->withSession(['magic' => $magic])->post(route('requests.store'), [
            'employee_requester' => $magic['email'],
            'employee_recipient' => $magic['email'],
            'event_cost_id' => 10,
            'account_name' => 'test',
            'iban' => 'NL90ABNA0123456789'
        ]);

        $response->assertStatus(302)->assertSessionHasErrors([
            'employee_requester' => 'De aanvrager mag niet het zelfde zijn als de ontvanger.'
        ]);
    }

    public function test_request_cannot_be_made_if_event_has_not_happened_for_recipient_AUTO(): void
    {
        Member::factory()->create([
            'email' => 'zzijpalm@almere.nl'
        ]);
        $magic = $this->get_employee_magic();

        $response = $this->withSession(['magic' => $magic])->post(route('requests.store'), [
            'employee_requester' => $magic['email'],
            'employee_recipient' => 'zzijpalm@almere.nl',
            'event_cost_id' => 1,
            'account_name' => 'test',
            'iban' => 'NL90ABNA0123456789'
        ]);

        $response->assertStatus(302)->assertSessionHasErrors([
            'event_cost_id' => 'Het geselecteerde gebeurtenis valt niet binnen acceptabele periode. (3 maanden voor of na)'
        ]);
    }
}
