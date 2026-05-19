<?php

namespace App\Modernization\Commerce;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CommerceTransactionPolicy
{
    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runIdempotent(string $idempotencyKey, Closure $callback): mixed
    {
        return Cache::lock("commerce:{$idempotencyKey}", 10)->block(0, function () use ($callback): mixed {
            return DB::transaction(function () use ($callback): mixed {
                return $callback();
            });
        });
    }

    /**
     * @return array{duplicate: bool, idempotency_key: string, transactional: bool, database_transaction: string}
     */
    public function duplicatePolicy(string $idempotencyKey): array
    {
        return [
            'duplicate' => false,
            'idempotency_key' => $idempotencyKey,
            'transactional' => true,
            'database_transaction' => 'DB::transaction',
        ];
    }
}
