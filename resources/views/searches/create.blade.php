@extends('layouts.app')

@section('title', 'GeoScan — Nouvelle recherche')

@section('content')
    <div class="card">
        <h1>Nouvelle recherche Shodan</h1>
        <p class="muted">
            Requête de recherche libre (ex. <code>apache</code>, <code>nginx</code>, <code>webcam</code>).
            Sans compte Shodan connecté, les filtres avancés (<code>country:</code>, <code>port:</code>, <code>org:</code>…)
            sont refusés par Shodan lui-même — utilise une requête libre.
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
@endsection
