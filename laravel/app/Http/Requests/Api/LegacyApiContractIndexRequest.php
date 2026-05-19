<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class LegacyApiContractIndexRequest extends FormRequest
{
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
            'protocol' => ['sometimes', 'string', Rule::in(['SOAP', 'XML-RPC', 'REST/API2'])],
            'role' => ['sometimes', 'string', Rule::in(['admin role', 'customer role', 'guest role', 'integration role'])],
            'feature_id' => ['sometimes', 'string', Rule::in(['API-001', 'API-002', 'API-003', 'API-004', 'API-005', 'API-006'])],
        ];
    }
}
