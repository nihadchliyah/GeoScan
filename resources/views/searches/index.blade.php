@extends('layouts.app')

@section('title', 'GeoScan — Historique')

@section('content')
    <div class="card">
        <h1>Historique des recherches</h1>
        <p class="muted">Consulter une entrée réaffiche l'archive telle quelle — aucune requête n'est renvoyée vers Shodan.</p>

        @if ($searches->isEmpty())
            <p class="muted">Aucune recherche enregistrée pour l'instant. <a href="{{ route('searches.create') }}">Lancer la première</a>.</p>
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
