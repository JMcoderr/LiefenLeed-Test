<?php

namespace Tests\Feature;

use App\Mail\MagicLoginMail;
use App\Models\Admin;
use App\Services\MagicLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) =>
                $page->component('auth/MagicLogin')
            );
    }

    public function test_user_can_authenticate_with_valid_credentials()
    {
        Mail::fake();
        $email = 'icttest@almere.nl';

        $response = $this->post(route('login'), [
            'email' => $email
        ]);

        $response->assertStatus(302);

        Mail::assertSent(MagicLoginMail::class, fn (MagicLoginMail $mail) =>
            $mail->hasTo($email)
        );
    }

    public function test_user_cannot_authenticate_with_invalid_credentials()
    {
        $email = 'test@example.nl';

        $response = $this->post(route('login'), [
            'email' => $email
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
    }

    public function test_user_authenticate_with_magic_link()
    {
        $magic = new MagicLinkService();

        $email = 'icttest@almere.nl';
        $token = $magic->generateToken($email);
        $url = $magic->createSignedUrl($email, $token);

        $response = $this->get($url);

        $response->assertStatus(302)->assertSessionHas('magic')->assertSessionHas('magic.email', $email);
    }

    public function test_user_cannot_authenticate_with_invalid_magic_link()
    {
        $magic = new MagicLinkService();

        $email = 'test@example.nl';
        $token = $magic->generateToken($email);
        $url = $magic->createSignedUrl($email, $token);

        $response = $this->get($url);

        $response->assertStatus(302)->assertSessionHasErrors('email');
    }

    public function test_admin_authenticate_with_magic_link()
    {
        $email = 'icttest@almere.nl';
        Admin::factory()->create(['employee' => $email]);
        $magic = new MagicLinkService();

        $token = $magic->generateToken($email);
        $url = $magic->createSignedUrl($email, $token);

        $response = $this->get($url);

        $response->assertStatus(302)->assertSessionHas('magic')->assertSessionHas('magic.email', $email)->assertSessionHas('magic.admin.isAdmin', true);
    }

    public function test_super_authenticate_with_magic_link()
    {
        $email = 'icttest@almere.nl';

        Admin::factory()->create(['employee' => $email, 'super' => now()]);
        $magic = new MagicLinkService();

        $token = $magic->generateToken($email);
        $url = $magic->createSignedUrl($email, $token);

        $response = $this->get($url);

        $response->assertStatus(302)
            ->assertSessionHas('magic')
            ->assertSessionHas('magic.email', $email)
            ->assertSessionHas('magic.admin.isAdmin', true)
            ->assertSessionHas('magic.admin.isSuper', true);
    }

    public function test_user_logsout_with_expired_session()
    {
        $session = [
            'magic' => [
                'id' => 1,
                'email' => 'test@almere.nl',
                'expires_at' => now()->subHour(),
                'admin' => [
                    'isAdmin' => false,
                    'isSuper' => false
                ]
            ]
        ];

        $response = $this->withSession($session)->get(route('requests'));

        $response->assertStatus(302)->assertRedirect(route('login'))->assertSessionMissing('magic');
    }

    public function test_user_can_logout()
    {
        $session = [
            'magic' => [
                'id' => 1,
                'email' => 'test@almere.nl',
                'expires_at' => now()->addHour(),
                'admin' => [
                    'isAdmin' => false,
                    'isSuper' => false
                ]
            ]
        ];
        $response = $this->withSession($session)->get(route('logout'));

        $response->assertStatus(302)->assertRedirect(route('login'))->assertSessionMissing('magic');
    }

    public function test_user_valid_session_request_can_be_rendered()
    {
        $session = [
            'magic' => [
                'id' => 1,
                'email' => 'test@almere.nl',
                'expires_at' => now()->addHour(),
                'admin' => [
                    'isAdmin' => false,
                    'isSuper' => false
                ]
            ]
        ];
        $response = $this->withSession($session)->get(route('requests'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) =>
                $page->component('Request')->has('events', 17)
            );
    }

    public function test_user_not_admin_cannot_render_requests()
    {
        $session = [
            'magic' => [
                'id' => 1,
                'email' => 'test@almere.nl',
                'expires_at' => now()->addHour(),
                'admin' => [
                    'isAdmin' => false,
                    'isSuper' => false
                ]
            ]
        ];

        $response = $this->withSession($session)->get(route('admin.requests.index'));

        $response->assertStatus(302)->assertRedirect(route('requests'));
    }

    public function test_admin_can_render_requests()
    {
        Admin::factory()->create(['employee' => 1, 'super' => false]);
        $session = [
            'magic' => [
                'id' => 1,
                'email' => 'test@almere.nl',
                'expires_at' => now()->addHour(),
                'admin' => [
                    'isAdmin' => true,
                    'isSuper' => false
                ]
            ]
        ];

        $response = $this->withSession($session)->get(route('admin.requests.index', ['statuses' => ['pending']]));

        $response->assertStatus(200)->assertInertia(fn ($page) => $page->component('admin/Requests')->has('selectedStatuses', 1));
    }

    public function test_admin_cannot_render_admins()
    {
        $session = [
            'magic' => [
                'id' => 1,
                'email' => 'test@almere.nl',
                'expires_at' => now()->addHour(),
                'admin' => [
                    'isAdmin' => true,
                    'isSuper' => false
                ]
            ]
        ];

        $response = $this->withSession($session)->get(route('admin.admins.index'));

        $response->assertStatus(302)->assertRedirect(route('admin.requests.index'));
    }

    public function test_super_can_render_requests()
    {
        $session = [
            'magic' => [
                'id' => 1,
                'email' => 'test@almere.nl',
                'expires_at' => now()->addHour(),
                'admin' => [
                    'isAdmin' => true,
                    'isSuper' => true
                ]
            ]
        ];

        $response = $this->withSession($session)->get(route('admin.requests.index'));

        $response->assertStatus(200)->assertInertia(fn ($page) => $page->component('admin/Requests')->has('selectedStatuses'));
    }

    public function test_super_can_render_admins()
    {
        Admin::factory(5)->create();
        $session = [
            'magic' => [
                'email' => 'test@almere.nl',
                'expires_at' => now()->addHour(),
                'admin' => [
                    'isAdmin' => true,
                    'isSuper' => true
                ]
            ]
        ];

        $response = $this->withSession($session)->get(route('admin.admins.index'));

        $response->assertStatus(200)->assertInertia(fn ($page) => $page->component('admin/Admin')->has('admins', 5));
    }

    public function test_admin_can_login_with_magic_link()
    {
        $email =  'mweber@almere.nl';

        Admin::factory()->create(['employee' => $email, 'super' => null]);

        $magic = new MagicLinkService();
        $token = $magic->generateToken($email);
        $url = $magic->createSignedUrl($email, $token);

        $response = $this->get($url);

        $response->assertStatus(302)->assertSessionHas('magic.admin.isAdmin', true)->assertSessionHas('magic.admin.isSuper', false);
    }

    public function test_super_can_login_with_magic_link()
    {
        $email =  'mweber@almere.nl';

        Admin::factory()->create(['employee' => $email, 'super' => now()]);

        $magic = new MagicLinkService();
        $token = $magic->generateToken($email);
        $url = $magic->createSignedUrl($email, $token);

        $response = $this->get($url);

        $response->assertStatus(302)->assertSessionHas('magic.admin.isAdmin', true)->assertSessionHas('magic.admin.isSuper', true);
    }
}
