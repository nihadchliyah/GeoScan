<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GeoScan')</title>
    @yield('head')
    <style>
        :root {
            color-scheme: light dark;
            --bg: #0f1115;
            --surface: #171a21;
            --border: #2a2f3a;
            --text: #e6e8eb;
            --text-muted: #9aa2af;
            --accent: #ff5a1f;
            --accent-soft: rgba(255, 90, 31, .18);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        header.topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        header.topbar .brand { font-weight: 700; font-size: 1.1rem; letter-spacing: .02em; }
        header.topbar .brand span { color: var(--accent); }
        header.topbar nav { display: flex; align-items: center; gap: 1.25rem; }
        header.topbar nav a { color: var(--text-muted); font-size: .9rem; }
        header.topbar nav a:hover { color: var(--text); text-decoration: none; }
        header.topbar nav form { display: flex; gap: .4rem; }
        header.topbar nav form input {
            width: 9rem;
            padding: .3rem .5rem;
            border-radius: .3rem;
            border: 1px solid var(--border);
            background: #0d0f13;
            color: var(--text);
            font-size: .85rem;
        }
        header.topbar nav form button {
            margin: 0;
            padding: .3rem .7rem;
            font-size: .85rem;
        }
        main {
            max-width: 960px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: .5rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        h1 { font-size: 1.4rem; margin: 0 0 1rem; }
        h2 { font-size: 1.05rem; margin: 0 0 .75rem; color: var(--text); }
        label { display: block; font-size: .85rem; color: var(--text-muted); margin-bottom: .35rem; }
        input[type=text] {
            width: 100%;
            padding: .6rem .75rem;
            border-radius: .4rem;
            border: 1px solid var(--border);
            background: #0d0f13;
            color: var(--text);
            font-size: 1rem;
        }
        button {
            margin-top: 1rem;
            padding: .6rem 1.2rem;
            border-radius: .4rem;
            border: none;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { opacity: .9; }
        .muted { color: var(--text-muted); font-size: .85rem; }
        .error { color: #ff6b6b; font-size: .85rem; margin-top: .5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem .25rem; border-bottom: 1px solid var(--border); font-size: .92rem; }
        th { color: var(--text-muted); font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; }
        .bar-row { margin-bottom: .5rem; }
        .bar-row .bar-label { display: flex; justify-content: space-between; font-size: .85rem; margin-bottom: .2rem; }
        .bar-row .bar-track { background: #0d0f13; border-radius: .3rem; overflow: hidden; height: .55rem; }
        .bar-row .bar-fill { background: var(--accent); height: 100%; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 700px) { .grid-2 { grid-template-columns: 1fr; } }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .filter-grid label { margin-bottom: .25rem; }
        details.filters { margin-top: 1rem; }
        details.filters summary { cursor: pointer; color: var(--text-muted); font-size: .9rem; }
        details.filters summary:hover { color: var(--text); }
        .badge {
            display: inline-block;
            padding: .1rem .5rem;
            border-radius: .3rem;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .badge-on { background: rgba(90, 200, 120, .18); color: #5ac878; }
        .badge-off { background: rgba(255, 90, 31, .18); color: var(--accent); }
        .pill {
            display: inline-block;
            padding: .15rem .55rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: .78rem;
            font-weight: 600;
            margin: 0 .3rem .3rem 0;
        }
        .timeline { border-left: 2px solid var(--border); padding-left: 1rem; margin-left: .25rem; }
        .timeline-entry { position: relative; padding-bottom: 1.25rem; }
        .timeline-entry::before {
            content: '';
            position: absolute;
            left: -1.31rem;
            top: .2rem;
            width: .6rem;
            height: .6rem;
            border-radius: 50%;
            background: var(--accent);
        }
        .stat { font-size: 1.6rem; font-weight: 700; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand"><span>Geo</span>Scan</div>
        <nav>
            <a href="{{ route('searches.create') }}">Nouvelle recherche</a>
            <a href="{{ route('searches.index') }}">Historique</a>
            <form action="{{ route('hosts.lookup') }}" method="GET">
                <input type="text" name="ip" placeholder="Fiche hôte : IP">
                <button type="submit">Voir</button>
            </form>
        </nav>
    </header>
    <main>
        @if (session('error'))
            <div class="card" style="border-color:#ff6b6b;">
                <p class="error" style="margin:0;">{{ session('error') }}</p>
            </div>
        @endif
        @yield('content')
    </main>
    @yield('scripts')
</body>
</html>
