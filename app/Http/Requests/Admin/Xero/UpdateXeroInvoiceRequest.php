<?php

namespace App\Http\Requests\Admin\Xero;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateXeroInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit-invoice') ?? false;
    }

    /**
     * Only DRAFT / SUBMITTED invoices may be edited (enforced again in
     * the controller against live Xero state before calling the API).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'xero_contact_id' => ['sometimes', 'integer', 'exists:xero_contacts,id'],
            'status' => ['sometimes', 'string', 'in:DRAFT,SUBMITTED,AUTHORISED'],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'branding_theme_id' => ['nullable', 'string', 'max:50'],
            'line_items' => ['sometimes', 'array', 'min:1', 'max:200'],
            'line_items.*.line_item_id' => ['nullable', 'string', 'max:50'],
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
            'xero_contact_id.exists' => 'The selected customer is invalid.',
            'line_items.min' => 'At least one line item is required.',
        ];
    }
}
