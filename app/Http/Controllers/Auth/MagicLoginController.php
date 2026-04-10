<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MagicRequest;
use App\Mail\MagicLoginMail;
use App\Models\Admin;
use App\Services\MagicLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MagicLoginController extends Controller
{
    public function __construct(protected MagicLinkService $magic)
    {
    }

    public function login()
    {
        return Inertia::render('auth/MagicLogin');
    }

    /**
     * @param MagicRequest $request
     * @return RedirectResponse
     * @author Brighton van Rouendal, Wim van Ginkel
     */
    public function sendLink(MagicRequest $request)
    {
        $token = $this->magic->generateToken($request->email);
        $url = $this->magic->createSignedUrl($request->email, $token);

        Mail::to($request->email)->send(new MagicLoginMail($url));

        return back();
    }

    public function verify(MagicRequest $request)
    {
        $request->validate(['token' => ['required', 'string', 'size:32']]);

        if (!$this->magic->isValidToken($request->email, $request->token))
            abort(403, 'Invalid or expired login token.');

        $this->magic->invalidateToken($request->email);

        $admin = Admin::query()->where('employee', $request->email)->first();

        // Store session for 24 hours
        session([
            'magic' => [
                'email' => strtolower($request->email),
                'expires_at' => now()->addHours(24),
                'admin' => [
                    'isAdmin' => (bool)$admin,
                    'isSuper' => $admin && (boolean)$admin->super,
                ],
            ]
        ]);

        return redirect()->route('admin.requests.index');
    }

    public function logout()
    {
        session()->flush();
        session()->regenerate();
        session()->regenerateToken();
        return redirect()->route('login');
    }
}
