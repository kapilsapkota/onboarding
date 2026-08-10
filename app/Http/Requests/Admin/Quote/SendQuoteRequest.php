<?php

namespace App\Http\Requests\Admin\Quote;

use Illuminate\Foundation\Http\FormRequest;

class SendQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Uses the auth middleware on the route group.
        // Add a policy check here if role-based authorization is added later.
        return true;
    }

    public function rules(): array
    {
        return [
            // At least one channel must be selected.
            // The after() validation below enforces the at-least-one rule.
            'send_email'        => ['nullable', 'boolean'],
            'send_sms'          => ['nullable', 'boolean'],

            // Additional messages — optional, length-limited.
            'extra_message'     => ['nullable', 'string', 'max:2000'],
            'extra_sms_message' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'extra_sms_message.max' => 'The SMS message must not exceed 300 characters.',
            'extra_message.max'     => 'The email message must not exceed 2000 characters.',
        ];
    }

    /**
     * Additional validation after the basic rules pass.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            $wantsEmail = (bool) $this->input('send_email');
            $wantsSms   = (bool) $this->input('send_sms');

            if (! $wantsEmail && ! $wantsSms) {
                $v->errors()->add(
                    'send_email',
                    'Please select at least one delivery method (Email or SMS).'
                );
            }
        });
    }

    /**
     * Cast checkbox values to booleans before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'send_email' => filter_var($this->input('send_email'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'send_sms'   => filter_var($this->input('send_sms'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
        ]);
    }
}
