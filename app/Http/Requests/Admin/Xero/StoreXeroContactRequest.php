<?php

namespace App\Http\Requests\Admin\Xero;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreXeroContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create-contact') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'xero_tenant_id' => ['required', 'integer', 'exists:xero_tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'bank_account_details' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'is_customer' => ['sometimes', 'boolean'],
            'is_supplier' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'xero_tenant_id.exists' => 'The selected organisation is invalid.',
            'name.required' => 'A contact name is required by Xero.',
        ];
    }
}
