<?php

namespace App\Http\Requests;

use App\Models\Alert;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertStatusRequest extends FormRequest
{
    /**
     * The route already sits behind the auth middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    Alert::STATUS_RESPONDING,
                    Alert::STATUS_ACKNOWLEDGED,
                    Alert::STATUS_RESOLVED,
                ]),
            ],
        ];
    }
}
