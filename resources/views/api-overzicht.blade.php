<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>API-overzicht</title>
        <style>
            :root {
                --bg: #f4f1ea;
                --card: #ffffff;
                --line: #d8d1c3;
                --text: #1e1b16;
                --muted: #6f675c;
                --accent: #7a4d2a;
                --accent-soft: #efe4d8;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: "Segoe UI", Tahoma, sans-serif;
                color: var(--text);
                background: var(--bg);
            }

            .page {
                width: min(1100px, calc(100% - 2rem));
                margin: 0 auto;
                padding: 2rem 0 3rem;
            }

            .hero,
            .panel {
                padding: 1.25rem;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: var(--card);
            }

            .hero {
                margin-bottom: 1rem;
            }

            .hero h1,
            .panel h2 {
                margin-top: 0;
            }

            .hero p,
            .panel p,
            .panel li {
                color: var(--muted);
                line-height: 1.6;
            }

            .hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .button-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.8rem 1rem;
                border-radius: 999px;
                font: inherit;
                text-decoration: none;
            }

            .button-link.primary {
                color: #fff;
                background: var(--accent);
            }

            .button-link.secondary {
                color: var(--text);
                background: var(--accent-soft);
            }

            .layout {
                display: grid;
                gap: 1rem;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
            }

            th,
            td {
                padding: 0.85rem;
                border-bottom: 1px solid var(--line);
                text-align: left;
                vertical-align: top;
            }

            th {
                color: var(--muted);
                font-size: 0.95rem;
            }

            code {
                font-family: Consolas, monospace;
                font-size: 0.95rem;
            }

            .method-list {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .method {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.6rem;
                border-radius: 999px;
                background: var(--accent-soft);
                color: var(--text);
                font-size: 0.9rem;
                font-weight: 600;
            }

            .group {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.6rem;
                border-radius: 999px;
                font-size: 0.9rem;
                font-weight: 600;
            }

            .group.web {
                background: #e7efe1;
                color: #294826;
            }

            .group.api {
                background: #efe4d8;
                color: #7a4d2a;
            }

            .hint {
                margin: 0;
                font-size: 0.95rem;
            }

            @media (max-width: 860px) {
                table,
                thead,
                tbody,
                tr,
                th,
                td {
                    display: block;
                }

                thead {
                    display: none;
                }

                tr {
                    padding: 0.9rem 0;
                    border-bottom: 1px solid var(--line);
                }

                td {
                    padding: 0.35rem 0;
                    border-bottom: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <section class="hero">
                <h1>API-overzicht</h1>
                <p>Deze pagina leest de bestaande routes uit de app. Daardoor blijft dit overzicht automatisch gelijk aan de echte routes in <code>routes/web.php</code> en <code>routes/api.php</code>.</p>
                <p>GET-routes kun je direct openen in de browser. Voor POST, PUT en DELETE zie je hier vooral welk pad je moet gebruiken.</p>

                <div class="hero-actions">
                    <a class="button-link primary" href="{{ route('home') }}">Terug naar de app</a>
                    <a class="button-link secondary" href="{{ url('/api/characters') }}" target="_blank" rel="noreferrer">Open karakterlijst</a>
                </div>
            </section>

            <div class="layout">
                <section class="panel">
                    <h2>Overzicht van web- en API-routes</h2>

                    @if ($sampleCharacter)
                        <p class="hint">Voor voorbeeldlinks met <code>{id}</code> wordt nu karakter ID <code>{{ $sampleCharacter->id }}</code> gebruikt.</p>
                    @else
                        <p class="hint">Er is nog geen karakter aanwezig, dus routes met <code>{id}</code> blijven nog als voorbeeldpad staan.</p>
                    @endif

                    <table>
                        <thead>
                            <tr>
                                <th>Soort</th>
                                <th>Methode</th>
                                <th>Route</th>
                                <th>Voorbeeld</th>
                                <th>Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appRoutes as $route)
                                <tr>
                                    <td>
                                        <span class="group {{ strtolower($route['group']) }}">{{ $route['group'] }}</span>
                                    </td>
                                    <td>
                                        <div class="method-list">
                                            @foreach ($route['methods'] as $method)
                                                <span class="method">{{ $method }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td><code>{{ $route['uri'] }}</code></td>
                                    <td><code>{{ $route['example_path'] }}</code></td>
                                    <td>
                                        @if ($route['can_open'])
                                            <a class="button-link secondary" href="{{ $route['example_url'] }}" target="_blank" rel="noreferrer">Open link</a>
                                        @else
                                            <span class="hint">Gebruik via API-client of frontend</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </body>
</html>
