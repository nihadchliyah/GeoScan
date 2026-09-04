@extends('layouts.app')

@section('title', 'GeoScan — Nouvelle recherche')

@section('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
    <div class="card">
        <h1>Nouvelle recherche Shodan</h1>
        <p class="muted">
            Requête de recherche libre (ex. <code>apache</code>, <code>nginx</code>, <code>webcam</code>).
            Sans compte Shodan connecté, les filtres avancés (<code>country:</code>, <code>port:</code>, <code>org:</code>…)
            sont refusés par Shodan lui-même — utilise une requête libre. Pour filtrer, sers-toi de
            la carte ci-dessous : elle porte sur tout ce qui a déjà été scrapé.
        </p>
        <form method="POST" action="{{ route('searches.store') }}">
            @csrf
            <label for="query">Requête</label>
            <input type="text" id="query" name="query" value="{{ old('query') }}" placeholder="apache" autofocus required>
            @error('query')
                <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit">Lancer la recherche</button>
        </form>
    </div>

    <div class="card">
        <h2>Carte des hôtes déjà scrapés</h2>
        <p class="muted">
            Filtre local, sans connexion à Shodan : porte sur les fiches hôte déjà collectées par
            toutes les recherches et consultations passées, pas sur une nouvelle requête. Lance
            plus de recherches libres ci-dessus pour enrichir cette base.
        </p>
        <form method="GET" action="{{ route('searches.create') }}">
            <div class="filter-grid">
                <div>
                    <label for="country">Pays</label>
                    <input type="text" id="country" name="country" value="{{ $filters['country'] ?? '' }}" placeholder="France">
                </div>
                <div>
                    <label for="city">Ville</label>
                    <input type="text" id="city" name="city" value="{{ $filters['city'] ?? '' }}" placeholder="Paris">
                </div>
                <div>
                    <label for="port">Port</label>
                    <input type="text" id="port" name="port" value="{{ $filters['port'] ?? '' }}" placeholder="80">
                </div>
                <div>
                    <label for="organization">Organisation</label>
                    <input type="text" id="organization" name="organization" value="{{ $filters['organization'] ?? '' }}" placeholder="Google LLC">
                </div>
                <div>
                    <label for="isp">FAI</label>
                    <input type="text" id="isp" name="isp" value="{{ $filters['isp'] ?? '' }}" placeholder="Orange">
                </div>
                <div>
                    <label for="asn">ASN</label>
                    <input type="text" id="asn" name="asn" value="{{ $filters['asn'] ?? '' }}" placeholder="AS15169">
                </div>
                <div>
                    <label for="product">Produit / techno</label>
                    <input type="text" id="product" name="product" value="{{ $filters['product'] ?? '' }}" placeholder="nginx">
                </div>
                <div>
                    <label for="hostname">Nom d'hôte / domaine</label>
                    <input type="text" id="hostname" name="hostname" value="{{ $filters['hostname'] ?? '' }}" placeholder="example.com">
                </div>
            </div>
            @error('port')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit">Filtrer</button>
            @if (collect($filters)->filter()->isNotEmpty())
                <a href="{{ route('searches.create') }}" class="muted" style="margin-left:.75rem;">Réinitialiser</a>
            @endif
        </form>

        <h3 style="margin-top:1.5rem;">{{ $hosts->count() }} hôte(s) localisé(s)</h3>
        @if ($hosts->isEmpty())
            <p class="muted">
                Aucun hôte scrapé ne correspond à ces filtres pour l'instant — lance des recherches
                libres pour en collecter, ou élargis les filtres.
            </p>
        @else
            <div id="map" style="height:420px;border-radius:.4rem;"></div>
            <p class="muted" style="margin:.5rem 0 0;">Clique un marqueur pour filtrer directement sur son pays ou son organisation.</p>
        @endif
    </div>
@endsection

@section('scripts')
    @if ($hosts->isNotEmpty())
        @php
            $markers = $hosts->map(fn ($snapshot) => [
                'ip' => $snapshot->host->ip,
                'organization' => $snapshot->organization,
                'country' => $snapshot->country,
                'label' => trim(($snapshot->city ?? '').($snapshot->city && $snapshot->country ? ', ' : '').($snapshot->country ?? '')) ?: null,
                'ports' => $snapshot->open_ports,
                'lat' => $snapshot->latitude,
                'lon' => $snapshot->longitude,
                'url' => route('hosts.show', $snapshot->host->ip),
                'countryFilterUrl' => $snapshot->country ? route('searches.create', ['country' => $snapshot->country]) : null,
                'organizationFilterUrl' => $snapshot->organization ? route('searches.create', ['organization' => $snapshot->organization]) : null,
            ]);
        @endphp
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function () {
                var markers = @json($markers);
                var map = L.map('map').setView([markers[0].lat, markers[0].lon], 3);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; contributeurs OpenStreetMap',
                    maxZoom: 18,
                }).addTo(map);

                var group = [];

                markers.forEach(function (m) {
                    var popup = '<strong>' + m.ip + '</strong>'
                        + (m.organization ? '<br>' + m.organization : '')
                        + (m.label ? '<br>' + m.label : '')
                        + (m.ports && m.ports.length ? '<br>ports : ' + m.ports.join(', ') : '')
                        + '<br><a href="' + m.url + '">voir la fiche</a>'
                        + (m.countryFilterUrl ? ' &middot; <a href="' + m.countryFilterUrl + '">filtrer ce pays</a>' : '')
                        + (m.organizationFilterUrl ? ' &middot; <a href="' + m.organizationFilterUrl + '">filtrer cette org.</a>' : '');

                    group.push(L.marker([m.lat, m.lon]).bindPopup(popup).addTo(map));
                });

                if (group.length > 1) {
                    map.fitBounds(L.featureGroup(group).getBounds(), { padding: [30, 30] });
                }
            })();
        </script>
    @endif
@endsection
