@extends('layouts.app')

@section('title', 'GeoScan — Nouvelle recherche')

@section('content')
    <div class="card">
        <h1>Nouvelle recherche Shodan</h1>
        <p class="muted">
            Requête de recherche libre (ex. <code>apache</code>, <code>nginx</code>, <code>webcam</code>).
            Les filtres avancés ci-dessous (pays, port…) demandent un compte Shodan connecté
            (<span class="badge {{ config('shodan.login.enabled') ? 'badge-on' : 'badge-off' }}">
                connexion {{ config('shodan.login.enabled') ? 'activée' : 'désactivée' }}
            </span>) — sans connexion, Shodan les refuse et l'erreur te le rappellera.
        </p>
        <form method="POST" action="{{ route('searches.store') }}">
            @csrf
            <label for="query">Requête</label>
            <input type="text" id="query" name="query" value="{{ old('query') }}" placeholder="apache" autofocus required>
            @error('query')
                <div class="error">{{ $message }}</div>
            @enderror

            <details class="filters" @if(old('country') || old('port') || old('org') || old('product') || old('os')) open @endif>
                <summary>Filtres avancés (pays, port, organisation…) — comme sur shodan.io</summary>
                <div class="filter-grid">
                    <div>
                        <label for="country">Pays</label>
                        <input type="text" id="country" name="country" value="{{ old('country') }}" placeholder="France">
                    </div>
                    <div>
                        <label for="port">Port</label>
                        <input type="text" id="port" name="port" value="{{ old('port') }}" placeholder="22">
                    </div>
                    <div>
                        <label for="org">Organisation</label>
                        <input type="text" id="org" name="org" value="{{ old('org') }}" placeholder="Google LLC">
                    </div>
                    <div>
                        <label for="product">Produit</label>
                        <input type="text" id="product" name="product" value="{{ old('product') }}" placeholder="Apache httpd">
                    </div>
                    <div>
                        <label for="os">Système d'exploitation</label>
                        <input type="text" id="os" name="os" value="{{ old('os') }}" placeholder="Windows">
                    </div>
                </div>
                @foreach (['country', 'port', 'org', 'product', 'os'] as $field)
                    @error($field)
                        <div class="error">{{ $message }}</div>
                    @enderror
                @endforeach
            </details>

            <button type="submit">Lancer la recherche</button>
        </form>
    </div>
@endsection
