<?php

namespace App\Http\Requests\Admin;

use App\Models\StripeAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateXeroTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('configure-xero') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Tenants originate from Xero OAuth, so only local links are editable:
     * the owning company, the default Stripe account for collections, and
     * the active flag. The Stripe account must belong to the linked company
     * (or be company-less); a company-less account is always allowed so the
     * legacy pool can serve unassigned organisations.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'default_stripe_account_id' => [
                'nullable',
                'integer',
                'exists:stripe_accounts,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $account = StripeAccount::find($value);

                    if (! $account) {
                        return;
                    }

                    $companyId = $this->input('company_id');

                    if ($companyId && $account->company_id && (int) $account->company_id !== (int) $companyId) {
                        $fail('The selected Stripe account belongs to a different company.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.exists' => 'The selected company does not exist.',
            'default_stripe_account_id.exists' => 'The selected Stripe account does not exist.',
        ];
    }
}
