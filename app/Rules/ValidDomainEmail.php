<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

class ValidDomainEmail implements ValidationRule
{
    protected array $validDomains;
    public function __construct(?array $validDomains = null)
    {
        $this->validDomains = $validDomains ?? Config::get('auth.allowed_domains', []);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL))
        {
            $fail('Het e-mailadres moet een valide e-mailadres zijn.');
            return;
        }

        // Find last occurnace of '@', strip the first character of string (this being the '@') and lower all characters to make string case insensitive
        $domain = strtolower(substr(strrchr($value, "@"), 1));

        if (!in_array($domain, $this->validDomains))
        {
            $fail('Het e-mailadres moet van een toegestaan domein zijn.');
            return;
        }
    }
}
