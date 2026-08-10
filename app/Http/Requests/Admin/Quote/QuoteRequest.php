<?php

namespace App\Http\Requests\Admin\Quote;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_name'           => 'required|string|max:255',
            'contact_name'          => 'nullable|string|max:255',
            'email'                 => 'required|email|max:255',
            'mobile'                => 'required|string|digits:10|starts_with:04,05',
            'website'               => 'nullable|string|max:255',
            'logo'                  => 'nullable|image|max:2048',
            'sharepoint_file_url'   => 'nullable|string|max:500',
            'sharepoint_source_url' => 'nullable|string|max:500',
            'notes'                 => 'nullable|string',
            'items'                 => ['required', 'json', 'min:1'],
            'items.*.product_id'    => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'      => ['required', 'numeric', 'min:1'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'items.*.setup_fee'     => ['nullable', 'numeric', 'min:0'],
            'expires_at'            => 'nullable|date|after:+1 week',
        ];
    }
}
