@extends('layouts.app')

@section('title', 'GeoScan — Historique')

@section('content')
    <div class="card">
        <h1>Historique des recherches</h1>
        <p class="muted">Consulter une entrée réaffiche l'archive telle quelle — aucune requête n'est renvoyée vers Shodan.</p>

        <form method="GET" action="{{ route('searches.index') }}">
            <div class="filter-grid">
                <div>
                    <label for="from">Du</label>
                    <input type="datetime-local" id="from" name="from" step="1" value="{{ request('from') }}">
                </div>
                <div>
                    <label for="to">Au</label>
                    <input type="datetime-local" id="to" name="to" step="1" value="{{ request('to') }}">
                </div>
            </div>
            @error('from')
                <div class="error">{{ $message }}</div>
            @enderror
            @error('to')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit">Filtrer</button>
            @if (request('from') || request('to'))
                <a href="{{ route('searches.index') }}" class="muted" style="margin-left:.75rem;">Réinitialiser</a>
            @endif
        </form>

        @if ($searches->isEmpty())
            <p class="muted">
                @if (request('from') || request('to'))
                    Aucune recherche archivée sur cette période.
                @else
                    Aucune recherche enregistrée pour l'instant. <a href="{{ route('searches.create') }}">Lancer la première</a>.
                @endif
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Requête</th>
                        <th>Résultats</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($searches as $search)
                        <tr>
                            <td><a href="{{ route('searches.show', $search) }}"><code>{{ $search->query }}</code></a></td>
                            <td>{{ number_format($search->total_results, 0, ',', ' ') }}</td>
                            <td class="muted">{{ $search->searched_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:1rem;">{{ $searches->links() }}</div>
        @endif
    </div>
@endsection
