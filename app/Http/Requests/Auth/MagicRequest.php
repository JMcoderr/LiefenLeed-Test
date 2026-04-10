<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class MagicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Decode email from base64 (GET method should have the email in URI as a parameter with the value base64 encoded)
        if ($this->isMethod('get')) {
            $decoded = base64_decode($this->input('email'));
            $this->merge(['email' => $decoded]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', new \App\Rules\ValidDomainEmail],
        ];
    }
}
