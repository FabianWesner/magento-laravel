<?php

namespace App\Modernization\Auth;

class FormKeyCompatibility
{
    /**
     * @return array{present: bool, valid: bool, csrf: string}
     */
    public function inspect(?string $submittedFormKey, ?string $sessionFormKey): array
    {
        return [
            'present' => $submittedFormKey !== null && $submittedFormKey !== '',
            'valid' => $submittedFormKey !== null && hash_equals((string) $sessionFormKey, $submittedFormKey),
            'csrf' => 'Laravel CSRF token remains required for state-changing web routes.',
        ];
    }
}
