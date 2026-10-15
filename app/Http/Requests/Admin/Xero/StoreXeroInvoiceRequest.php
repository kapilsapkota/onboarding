<?php

namespace App\Http\Requests\Admin\Xero;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreXeroInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create-invoice') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'xero_tenant_id' => ['required', 'integer', 'exists:xero_tenants,id'],
            'xero_contact_id' => ['required', 'integer', 'exists:xero_contacts,id'],
            'type' => ['required', 'string', 'in:ACCREC,ACCPAY'],
            'status' => ['required', 'string', 'in:DRAFT,SUBMITTED,AUTHORISED'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'branding_theme_id' => ['nullable', 'string', 'max:50'],
            'line_items' => ['required', 'array', 'min:1', 'max:200'],
            'line_items.*.description' => ['required', 'string', 'max:4000'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'line_items.*.account_code' => ['required', 'string', 'max:50'],
            'line_items.*.tax_type' => ['nullable', 'string', 'max:50'],
            'line_items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'xero_tenant_id.exists' => 'The selected organisation is invalid.',
            'xero_contact_id.exists' => 'The selected customer is invalid.',
            'line_items.min' => 'At least one line item is required.',
            'line_items.*.account_code.required' => 'Each line item needs a sales account code.',
        ];
    }
}
