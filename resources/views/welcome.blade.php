<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $company->company_name ?? config('app.name') }}</title>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --c-bg1: #0a0a1a;
            --c-bg2: #0f0f2e;
            --c-orb1: #6c3bff;
            --c-orb2: #00cfff;
            --c-orb3: #ff3baf;
            --c-glass-bg: rgba(255,255,255,0.06);
            --c-glass-border: rgba(255,255,255,0.14);
            --c-text: #f0f0ff;
            --c-muted: rgba(240,240,255,0.55);
            --c-staff: #00cfff;
            --c-admin: #a78bfa;
        }

        /* ── Base ───────────────────────────────────────────── */
        html, body { height: 100%; font-family: 'Segoe UI', system-ui, sans-serif; color: var(--c-text); overflow-x: hidden; }

        body {
            background: var(--c-bg1);
            min-height: 100vh;
        }

        /* ── Animated orb canvas ────────────────────────────── */
        .lp-canvas {
            position: fixed; inset: 0; z-index: 0; overflow: hidden;
            background: radial-gradient(ellipse at 20% 10%, #1a0a3e 0%, var(--c-bg1) 60%);
        }

        .lp-orb {
            position: absolute; border-radius: 50%;
            filter: blur(80px); opacity: 0.55;
        }
        .lp-orb-1 {
            width: 600px; height: 600px;
            background: var(--c-orb1);
            top: -150px; left: -150px;
            animation: lp-float1 16s ease-in-out infinite;
        }
        .lp-orb-2 {
            width: 500px; height: 500px;
            background: var(--c-orb2);
            bottom: -100px; right: -100px;
            animation: lp-float2 20s ease-in-out infinite;
        }
        .lp-orb-3 {
            width: 400px; height: 400px;
            background: var(--c-orb3);
            top: 40%; left: 55%;
            animation: lp-float3 18s ease-in-out infinite;
        }

        @keyframes lp-float1 {
            0%,100% { transform: translate(0,0) scale(1); }
            33%     { transform: translate(80px,60px) scale(1.1); }
            66%     { transform: translate(-40px,100px) scale(0.95); }
        }
        @keyframes lp-float2 {
            0%,100% { transform: translate(0,0) scale(1); }
            40%     { transform: translate(-100px,-60px) scale(1.08); }
            70%     { transform: translate(60px,-100px) scale(0.92); }
        }
        @keyframes lp-float3 {
            0%,100% { transform: translate(0,0) scale(1); }
            50%     { transform: translate(-80px,80px) scale(1.12); }
        }

        /* floating tiny orbs */
        .lp-particle {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.7);
            animation: lp-drift linear infinite;
        }

        @keyframes lp-drift {
            0%   { transform: translateY(100vh) translateX(0px); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 0.6; }
            100% { transform: translateY(-120px) translateX(var(--drift-x, 0px)); opacity: 0; }
        }

        /* ── Wrapper ────────────────────────────────────────── */
        .lp-wrap { position: relative; z-index: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ── Nav ────────────────────────────────────────────── */
        .lp-nav {
            display: flex; align-items: center; gap: 2rem;
            padding: 1.25rem 2.5rem;
            backdrop-filter: blur(18px) saturate(1.6);
            background: rgba(10,10,30,0.45);
            border-bottom: 1px solid var(--c-glass-border);
            position: sticky; top: 0; z-index: 100;
        }

        .lp-brand {
            display: flex; align-items: center; gap: .75rem;
            text-decoration: none; flex-shrink: 0;
        }
        .lp-brand-logo {
            height: 38px; width: auto;
            filter: drop-shadow(0 0 8px rgba(108,59,255,0.6));
        }
        .lp-brand-name {
            font-size: 1.2rem; font-weight: 700; letter-spacing: .02em;
            background: linear-gradient(135deg, #fff 30%, var(--c-orb2));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .lp-nav-items {
            display: flex; align-items: center; gap: .25rem;
            list-style: none; flex: 1;
        }
        .lp-nav-items a {
            color: var(--c-muted); text-decoration: none;
            font-size: .9rem; padding: .4rem .85rem; border-radius: 8px;
            transition: color .2s, background .2s;
        }
        .lp-nav-items a:hover {
            color: var(--c-text);
            background: rgba(255,255,255,0.07);
        }

        .lp-nav-auth {
            display: flex; align-items: center; gap: .6rem; margin-left: auto;
        }
        .lp-nav-auth a {
            text-decoration: none; font-size: .85rem; font-weight: 600;
            padding: .4rem 1rem; border-radius: 8px;
            transition: all .2s;
        }
        .lp-nav-auth .lp-btn-staff {
            color: var(--c-staff);
            border: 1px solid rgba(0,207,255,0.3);
        }
        .lp-nav-auth .lp-btn-staff:hover {
            background: rgba(0,207,255,0.1);
            border-color: var(--c-staff);
        }
        .lp-nav-auth .lp-btn-admin {
            color: var(--c-admin);
            border: 1px solid rgba(167,139,250,0.3);
        }
        .lp-nav-auth .lp-btn-admin:hover {
            background: rgba(167,139,250,0.12);
            border-color: var(--c-admin);
        }

        /* ── Hero ────────────────────────────────────────────── */
        .lp-hero {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 5rem 2rem 6rem;
        }

        .lp-hero-inner {
            display: flex; flex-direction: column; align-items: center;
            text-align: center; max-width: 720px;
        }

        /* 3D floating card */
        .lp-card {
            position: relative;
            background: var(--c-glass-bg);
            border: 1px solid var(--c-glass-border);
            border-radius: 28px;
            padding: 3.5rem 3rem;
            backdrop-filter: blur(24px) saturate(1.8);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 30px 80px rgba(0,0,0,0.5),
                0 0 120px rgba(108,59,255,0.12);
            transform: perspective(1200px) rotateX(0deg) rotateY(0deg);
            transition: transform .08s ease, box-shadow .2s ease;
            will-change: transform;
            width: 100%; max-width: 680px;
        }

        /* top shine line */
        .lp-card::before {
            content: '';
            position: absolute; top: 0; left: 10%; right: 10%; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
            border-radius: 50%;
        }

        .lp-logo-wrap {
            margin-bottom: 1.8rem;
            display: flex; justify-content: center;
        }
        .lp-logo-wrap img {
            height: 72px; width: auto;
            filter: drop-shadow(0 0 18px rgba(108,59,255,0.5));
        }
        .lp-logo-placeholder {
            width: 72px; height: 72px; border-radius: 18px;
            background: linear-gradient(135deg, var(--c-orb1), var(--c-orb2));
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 900; color: #fff;
            box-shadow: 0 0 30px rgba(108,59,255,0.4);
        }

        .lp-hero-title {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800; letter-spacing: -.02em; line-height: 1.1;
            background: linear-gradient(135deg, #fff 20%, var(--c-orb2) 80%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: .9rem;
        }

        .lp-hero-subtitle {
            font-size: 1.05rem; color: var(--c-muted);
            line-height: 1.6; margin-bottom: 2.8rem; max-width: 480px;
        }

        .lp-cta-row {
            display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
        }

        .lp-cta {
            display: inline-flex; align-items: center; gap: .55rem;
            text-decoration: none; font-size: 1rem; font-weight: 700;
            padding: .9rem 2.2rem; border-radius: 14px;
            transition: transform .18s, box-shadow .18s, filter .18s;
            position: relative; overflow: hidden;
        }
        .lp-cta::after {
            content: ''; position: absolute; inset: 0;
            background: rgba(255,255,255,0); transition: background .18s;
        }
        .lp-cta:hover::after { background: rgba(255,255,255,0.07); }
        .lp-cta:hover { transform: translateY(-3px) scale(1.02); }
        .lp-cta:active { transform: translateY(0) scale(0.98); }

        .lp-cta-staff {
            background: linear-gradient(135deg, #006aff, var(--c-staff));
            color: #fff;
            box-shadow: 0 8px 30px rgba(0,207,255,0.35);
        }
        .lp-cta-staff:hover { box-shadow: 0 12px 40px rgba(0,207,255,0.55); }

        .lp-cta-admin {
            background: linear-gradient(135deg, #5b21b6, var(--c-admin));
            color: #fff;
            box-shadow: 0 8px 30px rgba(167,139,250,0.35);
        }
        .lp-cta-admin:hover { box-shadow: 0 12px 40px rgba(167,139,250,0.55); }

        /* already-logged-in panel */
        .lp-cta-panel {
            background: linear-gradient(135deg, #065f46, #10b981);
            color: #fff;
            box-shadow: 0 8px 30px rgba(16,185,129,0.35);
        }
        .lp-cta-panel:hover { box-shadow: 0 12px 40px rgba(16,185,129,0.55); }

        .lp-cta svg { width: 20px; height: 20px; flex-shrink: 0; }

        /* ── 3D tilt – minimal/gradient styles ──────────────── */
        .lp-style-minimal .lp-card {
            background: rgba(255,255,255,0.92);
            border-color: rgba(0,0,0,0.08);
            box-shadow: 0 30px 80px rgba(0,0,0,0.15);
        }
        .lp-style-minimal .lp-hero-title,
        .lp-style-minimal .lp-brand-name { -webkit-text-fill-color: initial; color: #1e1b4b; }
        .lp-style-minimal .lp-hero-subtitle { color: #6b7280; }
        .lp-style-minimal .lp-nav-items a { color: #4b5563; }
        .lp-style-minimal .lp-nav-items a:hover { color: #111827; background: rgba(0,0,0,0.04); }
        .lp-style-minimal .lp-nav { background: rgba(255,255,255,0.85); border-color: rgba(0,0,0,0.08); }
        .lp-style-minimal .lp-brand-name { -webkit-text-fill-color: #1e1b4b; }

        .lp-style-gradient .lp-orb-1 { opacity: 0.7; filter: blur(60px); }
        .lp-style-gradient .lp-orb-2 { opacity: 0.65; filter: blur(60px); }
        .lp-style-gradient .lp-orb-3 { opacity: 0.6; filter: blur(60px); }
        .lp-style-gradient .lp-card {
            background: rgba(255,255,255,0.09);
            border-color: rgba(255,255,255,0.22);
            box-shadow: 0 30px 80px rgba(0,0,0,0.4), 0 0 160px rgba(108,59,255,0.18);
        }

        /* ── Footer ─────────────────────────────────────────── */
        .lp-footer {
            text-align: center; padding: 1.5rem;
            font-size: .78rem; color: rgba(240,240,255,0.3);
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        /* ── Responsive ─────────────────────────────────────── */
        @media (max-width: 640px) {
            .lp-nav { padding: .9rem 1.2rem; gap: 1rem; }
            .lp-nav-items { display: none; }
            .lp-card { padding: 2.5rem 1.5rem; }
            .lp-cta { padding: .75rem 1.5rem; font-size: .9rem; }
        }
    </style>
</head>
<body>
@php
    $company = \App\Models\CompanySetting::get();
    $config  = $company->getLandingConfig();
    $style   = $config['hero_style'] ?? 'glassmorphic';
    $logoUrl = $company->getLogoUrl();
    $initial = strtoupper(substr($company->company_name ?? 'O', 0, 1));
@endphp

{{-- Animated background --}}
<div class="lp-canvas lp-style-{{ $style }}">
    <div class="lp-orb lp-orb-1"></div>
    <div class="lp-orb lp-orb-2"></div>
    <div class="lp-orb lp-orb-3"></div>

    {{-- floating particles --}}
    @foreach(range(1,18) as $i)
    @php
        $size = rand(2,5);
        $left = rand(0,100);
        $dur  = rand(12,28);
        $del  = rand(0,20);
        $dx   = rand(-60,60);
    @endphp
    <div class="lp-particle" style="
        width:{{ $size }}px; height:{{ $size }}px;
        left:{{ $left }}%;
        animation-duration:{{ $dur }}s;
        animation-delay:-{{ $del }}s;
        --drift-x: {{ $dx }}px;
        opacity:{{ number_format(rand(3,7)/10,1) }};
    "></div>
    @endforeach
</div>

<div class="lp-wrap lp-style-{{ $style }}">

    {{-- ── Navigation ─────────────────────────────────────── --}}
    <nav class="lp-nav">
        <a href="/" class="lp-brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $company->company_name }}" class="lp-brand-logo">
            @else
                <div class="lp-logo-placeholder" style="width:36px;height:36px;font-size:1rem;border-radius:10px;">{{ $initial }}</div>
            @endif
            <span class="lp-brand-name">{{ $company->company_name }}</span>
        </a>

        @if(!empty($config['nav_items']))
        <ul class="lp-nav-items">
            @foreach($config['nav_items'] as $item)
            <li>
                <a href="{{ $item['url'] }}"
                   @if(!empty($item['new_tab'])) target="_blank" rel="noopener" @endif>
                    {{ $item['label'] }}
                </a>
            </li>
            @endforeach
        </ul>
        @endif

        <div class="lp-nav-auth">
            @auth
                @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))
                    <a href="{{ url('/admin') }}" class="lp-btn-admin">Dashboard</a>
                @else
                    <a href="{{ url('/app') }}" class="lp-btn-staff">Dashboard</a>
                @endif
            @else
                @if($config['show_staff_login'])
                    <a href="{{ url('/app/login') }}" class="lp-btn-staff">{{ $config['staff_login_label'] }}</a>
                @endif
                @if($config['show_admin_login'])
                    <a href="{{ url('/admin/login') }}" class="lp-btn-admin">{{ $config['admin_login_label'] }}</a>
                @endif
            @endauth
        </div>
    </nav>

    {{-- ── Hero ───────────────────────────────────────────── --}}
    <main class="lp-hero">
        <div class="lp-hero-inner">
            <div class="lp-card"
                 x-data="{
                    tilt(e) {
                        const r = $el.getBoundingClientRect();
                        const cx = r.left + r.width / 2;
                        const cy = r.top  + r.height / 2;
                        const rx = ((e.clientY - cy) / (r.height / 2)) * -7;
                        const ry = ((e.clientX - cx) / (r.width  / 2)) *  7;
                        $el.style.transform = `perspective(1200px) rotateX(${rx}deg) rotateY(${ry}deg)`;
                    },
                    reset() {
                        $el.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg)';
                    }
                 }"
                 @mousemove="tilt($event)"
                 @mouseleave="reset()">

                <div class="lp-logo-wrap">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $company->company_name }}">
                    @else
                        <div class="lp-logo-placeholder">{{ $initial }}</div>
                    @endif
                </div>

                <h1 class="lp-hero-title">{{ $config['hero_title'] }}</h1>
                <p class="lp-hero-subtitle">{{ $config['hero_subtitle'] }}</p>

                <div class="lp-cta-row">
                    @auth
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))
                            <a href="{{ url('/admin') }}" class="lp-cta lp-cta-panel">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ url('/app') }}" class="lp-cta lp-cta-panel">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Go to Dashboard
                            </a>
                        @endif
                    @else
                        @if($config['show_staff_login'])
                            <a href="{{ url('/app/login') }}" class="lp-cta lp-cta-staff">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $config['staff_login_label'] }}
                            </a>
                        @endif
                        @if($config['show_admin_login'])
                            <a href="{{ url('/admin/login') }}" class="lp-cta lp-cta-admin">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                {{ $config['admin_login_label'] }}
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </main>

    <footer class="lp-footer">
        &copy; {{ date('Y') }} {{ $company->company_name }}
        @if($company->website) &nbsp;&mdash;&nbsp; <a href="//{{ ltrim($company->website,'https://') }}" style="color:inherit" target="_blank" rel="noopener">{{ $company->website }}</a> @endif
    </footer>
</div>

{{-- Alpine.js (loaded by Filament/Livewire on authenticated pages, but we need it here too) --}}
@if(!app('router')->current()?->middleware() || !in_array('auth', app('router')->current()?->middleware() ?? []))
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endif
</body>
</html>
