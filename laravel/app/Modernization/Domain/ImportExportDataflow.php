<?php

namespace App\Modernization\Domain;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportExportDataflow
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: int, format: string, batch: bool, validation: string}
     *
     * @throws ValidationException
     */
    public function validateCsvRows(array $rows): array
    {
        Validator::make(['rows' => $rows], [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.sku' => ['required', 'string'],
            'rows.*.store_view' => ['required', 'string'],
        ])->validate();

        return [
            'rows' => count($rows),
            'format' => 'CSV',
            'batch' => true,
            'validation' => 'passed',
        ];
    }

    /**
     * @return array{error_file: string, failed_import: bool}
     */
    public function errorFile(string $profile): array
    {
        return [
            'error_file' => "var/importexport/{$profile}-errors.csv",
            'failed_import' => true,
        ];
    }
}
