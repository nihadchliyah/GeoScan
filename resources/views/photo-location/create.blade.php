@extends('layouts.app')

@section('title', 'GeoScan — Localiser une photo')

@section('content')
    <div class="card">
        <h1>Localiser une photo (EXIF)</h1>
        <p class="muted">
            Aucun rapport avec Shodan ni avec une adresse IP : certaines photos (prises par un
            téléphone avec la localisation activée) contiennent des coordonnées GPS dans leurs
            métadonnées EXIF. Cette page les lit directement dans le fichier — la photo n'est
            jamais enregistrée sur le serveur, seul son contenu est lu le temps de la requête.
            Formats supportés : JPEG/TIFF uniquement (le seul format qui porte l'EXIF — un PNG ou
            une capture d'écran n'en contiennent jamais).
        </p>
        <form method="POST" action="{{ route('photo-location.store') }}" enctype="multipart/form-data">
            @csrf
            <label for="photo">Photo (JPEG ou TIFF)</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/tiff" required>
            @error('photo')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit">Analyser</button>
        </form>
    </div>

    @if ($checked)
        <div class="card">
            @if ($coordinates)
                @php
                    [$lat, $lon] = $coordinates;
                    $delta = 0.15;
                    $bbox = ($lon - $delta).','.($lat - $delta).','.($lon + $delta).','.($lat + $delta);
                @endphp
                <h2>Localisation trouvée</h2>
                <iframe
                    width="100%" height="260" style="border:1px solid #2a2f3a;border-radius:.4rem;filter:grayscale(.15) contrast(1.05);"
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $bbox }}&amp;layer=mapnik&amp;marker={{ $lat }},{{ $lon }}"
                    loading="lazy" referrerpolicy="no-referrer"
                ></iframe>
                <p class="muted" style="margin:.5rem 0 0;">
                    {{ number_format($lat, 5) }}, {{ number_format($lon, 5) }} &middot;
                    <a href="https://www.openstreetmap.org/?mlat={{ $lat }}&amp;mlon={{ $lon }}#map=15/{{ $lat }}/{{ $lon }}" target="_blank" rel="noopener noreferrer">voir en grand sur OpenStreetMap</a>
                </p>
            @else
                <p class="muted">
                    Aucune coordonnée GPS trouvée dans les métadonnées EXIF de cette photo —
                    l'appareil n'avait probablement pas la localisation activée, ou les métadonnées
                    ont été retirées (beaucoup de réseaux sociaux et de messageries les suppriment
                    automatiquement à l'envoi).
                </p>
            @endif
        </div>
    @endif
@endsection
