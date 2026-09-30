<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Permission;

class UpdatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit-permission') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $permissionId = $this->route('permission') instanceof Permission
            ? $this->route('permission')->id
            : $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:permissions,name,'.$permissionId],
            'group' => ['required', 'string', 'max:50'],
        ];
    }
}
