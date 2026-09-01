@extends('layouts.app')

@section('title', 'GeoScan — ' . $host->ip)

@section('content')
    <div class="card">
        <p class="muted" style="margin:0 0 .5rem;">
            Dernier instantané récupéré le {{ $snapshot->fetched_at->format('d/m/Y H:i') }}
            @if ($snapshot->shodan_last_update)
                &middot; dernière observation Shodan : {{ $snapshot->shodan_last_update->format('d/m/Y') }}
            @endif
        </p>
        <h1>{{ $host->ip }}</h1>

        <div class="grid-2">
            <div>
                <h2>Informations générales</h2>
                <table>
                    <tbody>
                        <tr><th>Pays</th><td>{{ $snapshot->country ?? '—' }}</td></tr>
                        <tr><th>Ville</th><td>{{ $snapshot->city ?? '—' }}</td></tr>
                        <tr><th>Organisation</th><td>{{ $snapshot->organization ?? '—' }}</td></tr>
                        <tr><th>FAI</th><td>{{ $snapshot->isp ?? '—' }}</td></tr>
                        <tr><th>ASN</th><td>{{ $snapshot->asn ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <h2>Noms d'hôte &amp; domaines</h2>
                @forelse ($snapshot->hostnames ?? [] as $hostname)
                    <div class="pill">{{ $hostname }}</div>
                @empty
                    <p class="muted">Aucun nom d'hôte.</p>
                @endforelse
                @if (!empty($snapshot->domains))
                    <p class="muted" style="margin-top:.75rem;">Domaines : {{ implode(', ', $snapshot->domains) }}</p>
                @endif
            </div>
        </div>
    </div>

    @if ($snapshot->latitude !== null && $snapshot->longitude !== null)
        @php
            $lat = $snapshot->latitude;
            $lon = $snapshot->longitude;
            $delta = 0.15;
            $bbox = ($lon - $delta).','.($lat - $delta).','.($lon + $delta).','.($lat + $delta);
        @endphp
        <div class="card">
            <h2>Localisation</h2>
            <iframe
                width="100%" height="260" style="border:1px solid #2a2f3a;border-radius:.4rem;filter:grayscale(.15) contrast(1.05);"
                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $bbox }}&amp;layer=mapnik&amp;marker={{ $lat }},{{ $lon }}"
                loading="lazy" referrerpolicy="no-referrer"
            ></iframe>
            <p class="muted" style="margin:.5rem 0 0;">
                {{ number_format($lat, 4) }}, {{ number_format($lon, 4) }} &middot;
                <a href="https://www.openstreetmap.org/?mlat={{ $lat }}&amp;mlon={{ $lon }}#map=11/{{ $lat }}/{{ $lon }}" target="_blank" rel="noopener noreferrer">voir en grand sur OpenStreetMap</a>
            </p>
        </div>
    @endif

    <div class="grid-2">
        <div class="card">
            <h2>Ports ouverts</h2>
            @forelse ($snapshot->open_ports ?? [] as $port)
                <span class="pill">{{ $port }}</span>
            @empty
                <p class="muted">Aucun port ouvert détecté.</p>
            @endforelse
        </div>
        <div class="card">
            <h2>Technologies web</h2>
            @forelse ($snapshot->web_technologies ?? [] as $tech)
                <span class="pill">{{ $tech }}</span>
            @empty
                <p class="muted">Aucune technologie détectée.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2>Ligne du temps</h2>
        @if ($timeline->count() <= 1)
            <p class="muted">C'est le premier instantané enregistré pour cet hôte.</p>
        @else
            <div class="timeline">
                @foreach ($timeline as $entry)
                    <div class="timeline-entry">
                        <strong>{{ $entry->fetched_at->format('d/m/Y H:i') }}</strong>
                        @if ($loop->first)
                            <span class="pill">actuel</span>
                        @endif
                        <div class="muted">
                            {{ $entry->organization ?? '—' }} &middot;
                            {{ $entry->country ?? '—' }}@if($entry->city), {{ $entry->city }}@endif
                            &middot; ports : {{ !empty($entry->open_ports) ? implode(', ', $entry->open_ports) : '—' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
