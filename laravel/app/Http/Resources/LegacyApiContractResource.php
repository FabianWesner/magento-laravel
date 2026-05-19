<?php

namespace App\Http\Resources;

use App\Modernization\Api\LegacyApiContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LegacyApiContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LegacyApiContract $contract */
        $contract = $this->resource;

        return $contract->toArray();
    }
}
