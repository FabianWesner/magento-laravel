<section aria-label="Storefront parity panel">
    <header>
        <h2>Storefront parity</h2>
        <p>{{ $selectedFeature['legacy'] }}</p>
    </header>

    <nav aria-label="Storefront feature IDs">
        @foreach ($features as $id => $feature)
            <button type="button" wire:click="selectFeature('{{ $id }}')" @class(['is-active' => $featureId === $id])>
                {{ $id }} {{ $feature['label'] }}
            </button>
        @endforeach
    </nav>

    <dl>
        <dt>Fixture</dt>
        <dd>{{ $selectedFeature['fixture_id'] }}</dd>
        <dt>State</dt>
        <dd>{{ $selectedFeature['state'] }}</dd>
        <dt>Grand total</dt>
        <dd>{{ number_format($totals['grand_total'] ?? 0, 2) }}</dd>
        <dt>Edge states</dt>
        <dd>{{ implode(', ', $edgeStates) }}</dd>
    </dl>
</section>
