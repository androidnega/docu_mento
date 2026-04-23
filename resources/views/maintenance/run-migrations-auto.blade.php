<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Docu Mento — Database migrations</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0f14;
            --surface: #141922;
            --border: #252d3d;
            --text: #e8ecf4;
            --muted: #8b95a8;
            --accent: #34d399;
            --accent-dim: #065f46;
            --danger: #f87171;
            --warn: #fbbf24;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            background-image:
                radial-gradient(ellipse 120% 80% at 50% -30%, rgba(52, 211, 153, 0.12), transparent),
                radial-gradient(ellipse 60% 50% at 100% 100%, rgba(37, 45, 61, 0.5), transparent);
            color: var(--text);
            line-height: 1.5;
        }
        .wrap { max-width: 42rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 2rem;
        }
        .logo span { color: var(--accent); }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 0.5rem;
            line-height: 1.2;
        }
        .sub { color: var(--muted); font-size: 0.9375rem; margin-bottom: 2rem; }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.45);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.625rem;
            font-weight: 600;
            font-size: 0.9375rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #022c22;
            box-shadow: 0 4px 14px -2px rgba(16, 185, 129, 0.45);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.5); }
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover { color: var(--text); border-color: var(--muted); }
        .alert {
            border-radius: 0.75rem;
            padding: 1rem 1.125rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
        }
        .alert-warn { background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.25); color: #fde68a; }
        .alert-ok { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.25); color: #a7f3d0; }
        .alert-err { background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.25); color: #fecaca; }
        pre.out {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
            margin: 0;
            padding: 1rem;
            background: #0a0d12;
            border-radius: 0.5rem;
            border: 1px solid var(--border);
            color: #c4cdd9;
            max-height: 22rem;
            overflow: auto;
        }
        ul.steps { margin: 0; padding-left: 1.15rem; color: var(--muted); font-size: 0.875rem; }
        ul.steps li { margin-bottom: 0.35rem; }
        .foot { margin-top: 2rem; font-size: 0.75rem; color: var(--muted); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="logo">Docu <span>Mento</span> · Ops</div>

        @if(empty($secretOk))
            <h1>Access restricted</h1>
            <p class="sub">This page runs database migrations. Add your secret key to the URL query string (same as <code style="color:var(--accent);">MIGRATION_RUN_KEY</code> in <code style="color:var(--accent);">.env</code>).</p>
            <div class="alert alert-warn">
                Example: <span style="word-break:break-all;">https://documento.neckpressing.com/run-migrations-auto?key=YOUR_SECRET</span>
            </div>
            <p class="sub" style="margin-top:1.5rem;">If you have not set a custom key, the default from deployment docs may apply — change it in production.</p>
        @else
            <h1>Run migrations</h1>
            <p class="sub">Apply pending Laravel migrations and refresh caches. Use after deploying code that adds or changes tables.</p>

            @if(!empty($ran))
                @if(!empty($error))
                    <div class="alert alert-err"><strong>Something went wrong.</strong> {{ $error }}</div>
                @else
                    <div class="alert alert-ok"><strong>Done.</strong> Migrations and cache clears completed successfully.</div>
                @endif
                @if(!empty($output) || !empty($error))
                    <div class="card">
                        <div style="font-size:0.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.75rem;">Output</div>
                        <pre class="out">{{ $output ?: '(no console output)' }}</pre>
                    </div>
                @endif
                <a class="btn btn-ghost" href="{{ url()->current() }}?key={{ urlencode(request('key')) }}">← Back</a>
            @else
                <div class="card">
                    <ul class="steps">
                        <li>Runs <code style="color:var(--text);">php artisan migrate --force</code></li>
                        <li>Clears config, route, application, and view caches</li>
                        <li>Safe to run multiple times (only pending migrations apply)</li>
                    </ul>
                </div>
                <div class="alert alert-warn">Only run this when you trust this URL. Anyone with the key can modify the database schema.</div>
                @php
                    $runHref = url()->current() . '?' . http_build_query(['key' => request('key'), 'run' => '1']);
                @endphp
                <a class="btn btn-primary" href="{{ $runHref }}" onclick="return confirm('Run pending migrations on this server now?');">Run migrations now</a>
            @endif
        @endif

        <p class="foot">Docu Mento · {{ config('app.url') }}</p>
    </div>
</body>
</html>
