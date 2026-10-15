<?php

namespace App\Http\Requests\Admin\Xero;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateXeroRepeatingInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit-repeating-invoice') ?? false;
    }

    /**
     * Only DRAFT templates may be edited (enforced again in the
     * controller against live Xero state before calling the API).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'xero_contact_id' => ['sometimes', 'integer', 'exists:xero_contacts,id'],
            'status' => ['sometimes', 'string', 'in:DRAFT,AUTHORISED'],
            'schedule_period' => ['sometimes', 'integer', 'min:1', 'max:52'],
            'schedule_unit' => ['sometimes', 'string', 'in:WEEKLY,MONTHLY'],
            'schedule_due_date' => ['sometimes', 'integer', 'min:0'],
            'schedule_due_date_type' => ['sometimes', 'string', 'in:DAYSAFTERBILLDATE,DAYSAFTERBILLMONTH,OFTHECURRENTMONTH,OFTHEFOLLOWINGMONTH'],
            'schedule_start_date' => ['sometimes', 'date'],
            'schedule_end_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'branding_theme_id' => ['nullable', 'string', 'max:50'],
            'line_items' => ['sometimes', 'array', 'min:1', 'max:200'],
            'line_items.*.description' => ['required', 'string', 'max:4000'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'line_items.*.account_code' => ['required', 'string', 'max:50'],
            'line_items.*.tax_type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
