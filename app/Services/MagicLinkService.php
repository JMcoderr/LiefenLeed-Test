<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;


class MagicLinkService
{
    protected int $expiresMinutes;

    public function __construct()
    {
        $this->expiresMinutes = Config::get('services.magic.expires', 5);
    }

    public function generateToken(string $email): string
    {
        $token = Str::random(32);
        Cache::put($this->cacheKey($email), $token, now()->addMinutes($this->expiresMinutes));
        return $token;
    }

    public function createSignedUrl(string $email, string $token): string
    {
        return URL::temporarySignedRoute(
            'magic-login.verify',
            now()->addMinutes($this->expiresMinutes),
            [
                'token' => $token,
                'email' => base64_encode($email),
            ]
        );
    }

    public function isValidToken(string $email, string $token): bool
    {
        $storedToken = Cache::get($this->cacheKey($email));
        return $storedToken && hash_equals($storedToken, $token);
    }

    public function invalidateToken(string $email): void
    {
        Cache::forget($this->cacheKey($email));
    }

    protected function cacheKey(string $email): string
    {
        return "magic-token:" . strtolower($email);
    }
}
