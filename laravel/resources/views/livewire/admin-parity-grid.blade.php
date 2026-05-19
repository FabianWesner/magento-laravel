<section aria-label="Admin parity grid">
    <header>
        <h2>Admin parity</h2>
        <p>{{ $selectedRow['legacy'] }}</p>
    </header>

    <div role="toolbar" aria-label="Admin parity filters">
        <button type="button" wire:click="setFilter('all')">All</button>
        <button type="button" wire:click="setFilter('edge')">Edge</button>
        <button type="button" wire:click="setFilter('failure')">Failure</button>
    </div>

    <table>
        <thead>
            <tr>
                <th scope="col">Feature ID</th>
                <th scope="col">Fixture</th>
                <th scope="col">Coverage</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $id => $row)
                <tr @class(['is-active' => $featureId === $id])>
                    <th scope="row">
                        <button type="button" wire:click="selectFeature('{{ $id }}')">{{ $id }} {{ $row['label'] }}</button>
                    </th>
                    <td>{{ $row['fixture_id'] }}</td>
                    <td>{{ implode(', ', $row['coverage']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
