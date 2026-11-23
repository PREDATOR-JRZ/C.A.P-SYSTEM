<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CICS Admissions')</title>
    <style>
        :root {
            --brand:#0b2a5b;      /* dark blue  */
            --brand-2:#123a7a;    /* hover blue */
            --ink:#0a0a0a;        /* black      */
            --mute:#6b7280;       /* gray       */
            --line:#d9dce1;
            --bg:#eceef1;
            --ok:#1f6f4a; --bad:#a4262c; --warn:#8a5a00;
        }
        * { box-sizing:border-box }
        body { margin:0; font:15px/1.55 "Segoe UI",system-ui,-apple-system,Roboto,sans-serif; background:var(--bg); color:var(--ink) }
        a { color:var(--brand) }
        h1,h2,h3 { color:var(--ink); letter-spacing:-.01em }

        header.top { background:linear-gradient(90deg,#050505 0%,#0b1220 45%,var(--brand) 100%); color:#fff; padding:.8rem 1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.6rem; border-bottom:3px solid var(--brand-2); box-shadow:0 2px 10px rgba(0,0,0,.25) }
        header.top strong { font-size:1.1rem; letter-spacing:.04em; text-transform:uppercase }
        nav { display:flex; gap:.2rem; flex-wrap:wrap; align-items:center }
        nav a, nav button { color:#cfd4dc; text-decoration:none; padding:.45rem .8rem; border-radius:6px; background:none; border:0; font:inherit; cursor:pointer; transition:.15s }
        nav a:hover, nav button:hover { background:rgba(255,255,255,.12); color:#fff }
        nav a.active { background:#fff; color:var(--brand); font-weight:600 }

        main { max-width:1040px; margin:1.75rem auto; padding:0 1rem }
        .card { background:#fff; border:1px solid var(--line); border-top:3px solid var(--brand); border-radius:10px; padding:1.4rem; margin-bottom:1.1rem; box-shadow:0 1px 3px rgba(10,10,10,.06) }
        .card h2, .card h3 { margin-top:0 }
        .grid { display:grid; gap:1.1rem; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)) }
        .grid-form { display:grid; gap:.95rem; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)) }
        label { display:block; font-weight:600; font-size:.82rem; margin-bottom:.3rem; color:#374151; text-transform:uppercase; letter-spacing:.03em }
        input, select, textarea { width:100%; padding:.6rem .7rem; border:1px solid #c4c9d0; border-radius:8px; font:inherit; background:#fff; color:var(--ink) }
        input:focus, select:focus, textarea:focus { outline:2px solid var(--brand-2); outline-offset:1px; border-color:var(--brand-2) }

        .btn { display:inline-block; background:var(--brand); color:#fff; border:1px solid var(--brand); padding:.65rem 1.2rem; border-radius:8px; font:inherit; font-weight:600; cursor:pointer; text-decoration:none; transition:.15s }
        .btn:hover { background:var(--brand-2) }
        .btn:disabled, .btn[disabled] { background:#b6bcc6; border-color:#b6bcc6; cursor:not-allowed }
        .btn.ok { background:var(--ink); border-color:var(--ink) } .btn.ok:hover { background:#222 }
        .btn.bad { background:var(--bad); border-color:var(--bad) }
        .btn.ghost { background:#fff; color:var(--brand); border:1px solid var(--brand) } .btn.ghost:hover { background:#eef2fa }
        .btn.sm { padding:.3rem .7rem; font-size:.85rem }

        .alert { padding:.8rem 1rem; border-radius:8px; margin-bottom:1rem; border-left:4px solid }
        .alert.success { background:#eef2fa; color:var(--brand); border-color:var(--brand) }
        .alert.error { background:#f3f4f6; color:var(--bad); border-color:var(--bad) }
        .err { color:var(--bad); font-size:.82rem; margin-top:.2rem }
        .badge { display:inline-block; padding:.15rem .65rem; border-radius:999px; font-size:.78rem; font-weight:600; background:#e3e8f2; color:var(--brand) }
        .badge.ok { background:var(--ink); color:#fff } .badge.bad { background:#f6e3e4; color:var(--bad) } .badge.warn { background:#e5e7eb; color:#374151 }

        table { width:100%; border-collapse:collapse } th, td { text-align:left; padding:.6rem .5rem; border-bottom:1px solid var(--line); vertical-align:top }
        th { font-size:.74rem; color:var(--mute); text-transform:uppercase; letter-spacing:.05em }
        .muted { color:var(--mute) } .row { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center }
        code { background:#e5e7eb; padding:.1rem .35rem; border-radius:4px }

        .tracker { list-style:none; padding:0; margin:0; display:grid; gap:.5rem }
        .tracker li { display:flex; gap:.75rem; align-items:center; padding:.55rem .8rem; border:1px solid var(--line); border-radius:8px; background:#fafafa }
        .dot { width:1.55rem; height:1.55rem; border-radius:50%; display:grid; place-items:center; font-size:.8rem; font-weight:700; background:#d1d5db; color:#4b5563 }
        .tracker li.done { background:#fff } .tracker li.done .dot { background:var(--brand); color:#fff }
        .tracker li.current { border-color:var(--brand); background:#eef2fa; font-weight:600 } .tracker li.current .dot { background:var(--ink); color:#fff }
        .tracker li.failed { border-color:var(--bad) } .tracker li.failed .dot { background:var(--bad); color:#fff }

        .auth-wrap { max-width:430px; margin:2.5rem auto }
        .auth-wrap .card { border-top:0; padding:0; overflow:hidden }
        .auth-head { background:linear-gradient(120deg,#050505,var(--brand)); color:#fff; padding:1.4rem 1.5rem }
        .auth-head h2 { color:#fff; margin:0 } .auth-head p { margin:.25rem 0 0; color:#c8d0de }
        .auth-body { padding:1.4rem 1.5rem }

        @media print { header.top, .noprint { display:none } body { background:#fff } .card { box-shadow:none } }
    </style>
</head>
<body>
<header class="top">
    <strong>CICS Admissions Portal</strong>
    <nav>
        @auth
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Dashboard</a>
                <a href="{{ route('admin.applications.index') }}" @class(['active' => request()->routeIs('admin.applications.*')])>Applications</a>
                <a href="{{ route('admin.announcements.index') }}" @class(['active' => request()->routeIs('admin.announcements.*')])>Announcements</a>
            @else
                <a href="{{ route('student.dashboard') }}" @class(['active' => request()->routeIs('student.dashboard')])>Dashboard</a>
                <a href="{{ route('student.profile') }}" @class(['active' => request()->routeIs('student.profile')])>Personal Information</a>
                <a href="{{ route('student.status') }}" @class(['active' => request()->routeIs('student.status')])>Status</a>
                <a href="{{ route('student.checklist') }}" @class(['active' => request()->routeIs('student.checklist')])>Check List</a>
                <a href="{{ route('student.announcements') }}" @class(['active' => request()->routeIs('student.announcements')])>Announcements</a>
                <a href="{{ route('student.about') }}" @class(['active' => request()->routeIs('student.about')])>About CICS</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">@csrf <button>Logout</button></form>
        @endauth
    </nav>
</header>
<main>
    @if (session('success')) <div class="alert success">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert error">{{ session('error') }}</div> @endif
    @yield('content')
</main>
</body>
</html>
