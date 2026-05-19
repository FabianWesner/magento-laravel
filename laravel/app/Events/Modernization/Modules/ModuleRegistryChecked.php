<?php

namespace App\Events\Modernization\Modules;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ModuleRegistryChecked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $health
     */
    public function __construct(public readonly array $health) {}
}
