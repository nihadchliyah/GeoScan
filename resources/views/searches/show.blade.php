@extends('layouts.app')

@section('title', 'GeoScan — ' . $search->query)

@section('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
    <div class="card">
        <p class="muted" style="margin:0 0 .5rem;">
            Recherche archivée le {{ $search->searched_at->format('d/m/Y H:i') }} &middot;
            <a href="{{ route('searches.index') }}">&larr; Historique</a>
        </p>
        <h1><code>{{ $search->query }}</code></h1>
        <div class="stat">{{ number_format($search->total_results, 0, ',', ' ') }}</div>
        <div class="muted">résultats au total</div>
        <p class="muted" style="margin:.75rem 0 0;">
            Tu as une image (capture de webcam, etc.) et tu veux savoir de quelle IP elle vient ?
            Shodan a son propre navigateur d'images, mais réservé aux comptes connectés (refusé en
            anonyme, testé) — cette appli ne s'y connecte pas, donc pas de recherche par image ici.
            Tu peux comparer manuellement, connecté avec ton propre compte, sur
            <a href="https://images.shodan.io/?query={{ urlencode($search->query) }}" target="_blank" rel="noopener noreferrer">images.shodan.io</a>.
        </p>
    </div>

    @if ($search->results_pending)
        <div class="card" id="results-pending-notice">
            <p class="muted" style="margin:0;">
                Localisation précise des résultats en cours ({{ $search->hostSnapshots->count() }}/{{ $search->expected_result_count }})…
                Cette page se rafraîchit automatiquement.
            </p>
        </div>
    @endif

    @php
        $grouped = $search->rankings->groupBy(fn ($r) => $r->type->value);
        $countryMarkers = $grouped->get('country', collect())
            ->map(function ($item) {
                $coords = \App\Support\CountryCoordinates::lookup($item->label);

                return $coords ? ['label' => $item->label, 'count' => $item->count, 'lat' => $coords[0], 'lon' => $coords[1]] : null;
            })
            ->filter()
            ->values();

        $resultMarkers = $search->hostSnapshots
            ->filter(fn ($snapshot) => $snapshot->latitude !== null && $snapshot->longitude !== null)
            ->map(fn ($snapshot) => [
                'ip' => $snapshot->host->ip,
                'label' => trim(($snapshot->city ?? '').($snapshot->city && $snapshot->country ? ', ' : '').($snapshot->country ?? '')) ?: null,
                'organization' => $snapshot->organization,
                'lat' => $snapshot->latitude,
                'lon' => $snapshot->longitude,
                'url' => route('hosts.show', $snapshot->host->ip),
            ])
            ->values();
    @endphp

    @if ($countryMarkers->isNotEmpty())
        <div class="card">
            <h2>Carte des pays (Top Countries)</h2>
            <div id="country-map" style="height:320px;border-radius:.4rem;"></div>
            <p class="muted" style="margin:.5rem 0 0;">
                Taille des cercles proportionnelle au nombre de résultats. Positions approximatives
                (centroïde du pays — Shodan ne donne pas de coordonnées par hôte sur cette page,
                seulement le total par pays).
            </p>
        </div>
    @endif

    @if ($resultMarkers->isNotEmpty())
        <div class="card">
            <h2>Carte des résultats (emplacement exact)</h2>
            <div id="results-map" style="height:360px;border-radius:.4rem;"></div>
            <p class="muted" style="margin:.5rem 0 0;">
                {{ $resultMarkers->count() }} résultat(s) localisé(s) précisément — coordonnées GPS
                réelles récupérées sur la fiche hôte de chaque IP (pas une approximation par pays).
                Clique un marqueur pour ouvrir sa fiche.
            </p>
        </div>
    @endif

    <div class="grid-2">
        @foreach (\App\Enums\RankingType::labels() as $type => $label)
            @php
                $items = $grouped->get($type, collect());
                $max = $items->max('count') ?: 1;
            @endphp
            <div class="card">
                <h2>{{ $label }}</h2>
                @forelse ($items as $item)
                    <div class="bar-row">
                        <div class="bar-label">
                            <span>{{ $item->label }}</span>
                            <span class="muted">{{ number_format($item->count, 0, ',', ' ') }}</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ round($item->count / $max * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="muted">Aucune donnée.</p>
                @endforelse
            </div>
        @endforeach
    </div>
@endsection

@section('scripts')
    @if ($search->results_pending)
        <script>
            setTimeout(function () { window.location.reload(); }, 5000);
        </script>
    @endif

    @if ($countryMarkers->isNotEmpty() || $resultMarkers->isNotEmpty())
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endif

    @if ($countryMarkers->isNotEmpty())
        <script>
            (function () {
                var markers = @json($countryMarkers);
                var map = L.map('country-map', { scrollWheelZoom: false }).setView([20, 10], 2);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; contributeurs OpenStreetMap',
                    maxZoom: 8,
                }).addTo(map);

                var maxCount = Math.max.apply(null, markers.map(function (m) { return m.count; })) || 1;

                markers.forEach(function (m) {
                    var radius = 8 + (m.count / maxCount) * 28;

                    L.circleMarker([m.lat, m.lon], {
                        radius: radius,
                        color: '#ff5a1f',
                        fillColor: '#ff5a1f',
                        fillOpacity: 0.45,
                        weight: 1,
                    }).bindTooltip(m.label + ' — ' + m.count.toLocaleString('fr-FR')).addTo(map);
                });
            })();
        </script>
    @endif

    @if ($resultMarkers->isNotEmpty())
        <script>
            (function () {
                var markers = @json($resultMarkers);
                var map = L.map('results-map').setView([markers[0].lat, markers[0].lon], 3);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; contributeurs OpenStreetMap',
                    maxZoom: 18,
                }).addTo(map);

                var group = [];

                markers.forEach(function (m) {
                    var popup = '<strong>' + m.ip + '</strong>'
                        + (m.organization ? '<br>' + m.organization : '')
                        + (m.label ? '<br>' + m.label : '')
                        + '<br><a href="' + m.url + '">voir la fiche</a>';

                    var marker = L.marker([m.lat, m.lon]).bindPopup(popup).addTo(map);
                    group.push(marker);
                });

                if (group.length > 1) {
                    map.fitBounds(L.featureGroup(group).getBounds(), { padding: [30, 30] });
                }
            })();
        </script>
    @endif
@endsection
