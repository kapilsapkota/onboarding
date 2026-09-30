<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStripeAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit-stripe-account') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Key fields are optional on update: blank means "keep the stored key"
     * (stored secrets are never shown back in the form).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'display_name' => ['required', 'string', 'max:100'],
            'publishable_key' => ['nullable', 'string', 'max:255', 'starts_with:pk_'],
            'secret_key' => ['nullable', 'string', 'max:500', 'starts_with:sk_,rk_'],
            'webhook_secret' => ['nullable', 'string', 'max:500', 'starts_with:whsec_'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:active,disabled'],
        ];
    }
}
