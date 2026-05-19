<?php

namespace App\Modernization\Commerce;

use App\Modernization\Commerce\Contracts\CommerceServiceContract;
use Illuminate\Support\Facades\Http;

class CommerceCalculator implements CommerceServiceContract
{
    public function __construct(private readonly CommerceCatalog $catalog) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function snapshot(string $featureKey, array $payload = []): array
    {
        $feature = $this->catalog->get($featureKey);

        return (new CommerceFeatureValueObject($feature, [
            'legacy comparison' => $payload['legacy result'] ?? null,
            'DB snapshot' => $payload['DB snapshot'] ?? [],
            'DB delta' => $payload['DB delta'] ?? [],
            'collector_sequence' => ['quote', 'cart', 'totals', 'pricing', 'promotion', 'tax', 'shipping', 'payment'],
            'totals' => $this->totals($payload),
            'edge' => $payload['edge'] ?? ['invalid quantity', 'permission denial', 'stale cache', 'rollback recovery'],
        ], 'legacy_runtime_fallback'))->toArray();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{subtotal: float, discount: float, tax: float, shipping: float, grand_total: float}
     */
    public function totals(array $payload): array
    {
        $subtotal = (float) collect($payload['items'] ?? [])->sum(
            fn (array $item): float => ((float) $item['price']) * ((int) $item['qty'])
        );
        $discount = (float) ($payload['coupon_discount'] ?? 0);
        $tax = round(max(0, $subtotal - $discount) * (float) ($payload['tax_rate'] ?? 0), 2);
        $shipping = (float) ($payload['shipping'] ?? 0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'grand_total' => round($subtotal - $discount + $tax + $shipping, 2),
        ];
    }

    public function externalRetryProbe(): string
    {
        $request = Http::timeout(2)->connectTimeout(1)->retry(2, 100, throw: false);

        return $request::class;
    }
}
