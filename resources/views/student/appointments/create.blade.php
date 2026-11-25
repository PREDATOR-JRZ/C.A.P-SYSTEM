@extends('layouts.app')
@section('title', $title)
@section('content')
<style>
    .dp-wrap { display:grid; gap:1.25rem; grid-template-columns:minmax(0,1.5fr) minmax(0,1fr) }
    @media (max-width:760px) { .dp-wrap { grid-template-columns:1fr } }
    .dp { border:1px solid var(--line); border-radius:12px; overflow:hidden; background:#fff }
    .dp-head { display:flex; justify-content:space-between; align-items:center; background:var(--ink); color:#fff; padding:.7rem 1rem }
    .dp-head strong { font-size:1.05rem; letter-spacing:.02em }
    .dp-nav { background:rgba(255,255,255,.12); color:#fff; border:0; width:2.1rem; height:2.1rem; border-radius:8px; font-size:1.1rem; cursor:pointer }
    .dp-nav:hover:not(:disabled) { background:var(--brand-2) } .dp-nav:disabled { opacity:.3; cursor:not-allowed }
    .dp-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; padding:.75rem }
    .dp-dow { text-align:center; font-size:.72rem; font-weight:700; color:var(--mute); text-transform:uppercase; padding:.25rem 0 }
    .dp-day { position:relative; aspect-ratio:1/1; min-height:48px; border:1px solid transparent; border-radius:8px; background:#f3f4f6; font:inherit; font-weight:600; color:var(--ink); cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; line-height:1.15; transition:.12s }
    .dp-day small { font-size:.62rem; font-weight:500; color:var(--mute) }
    .dp-day:hover:not(:disabled) { border-color:var(--brand); background:#e3e8f2 }
    .dp-day:disabled { background:transparent; color:#b6bcc6; cursor:not-allowed }
    .dp-day.full:disabled { background:#e5e7eb; color:#6b7280; text-decoration:line-through }
    .dp-day.low small { color:var(--bad); font-weight:700 }
    .dp-day.today { box-shadow:inset 0 0 0 1px #9ca3af }
    .dp-day.sel, .dp-day.sel:hover { background:var(--brand); color:#fff; border-color:var(--brand) } .dp-day.sel small { color:#c8d0de }
    .dp-blank { visibility:hidden }
    .dp-legend { display:flex; gap:1rem; flex-wrap:wrap; padding:0 1rem 1rem; font-size:.78rem; color:var(--mute) }
    .dp-legend i { display:inline-block; width:.8rem; height:.8rem; border-radius:3px; vertical-align:-1px; margin-right:.3rem }
    .summary { border:1px solid var(--line); border-radius:12px; padding:1.2rem; background:#fafafa; align-self:start }
    .summary .big { font-size:1.35rem; font-weight:700; color:var(--brand); margin:.2rem 0 }
    .meter { height:8px; background:#d9dce1; border-radius:99px; overflow:hidden; margin:.5rem 0 .25rem } .meter > div { height:100%; background:var(--brand) }
</style>

<div class="card">
    <h2>{{ $type === 'exam' ? 'Step 3' : 'Step 7' }} · {{ $title }}</h2>
    <p class="muted">Choose a date from the calendar. Only {{ config('cics.daily_slots') }} students are accepted per day, and appointments are available on weekdays only.</p>
    @error('appointment_date') <div class="alert error">{{ $message }}</div> @enderror

    <form method="POST" action="{{ route('student.appointments.store', $type) }}" id="apptForm">
        @csrf
        <input type="hidden" name="appointment_date" id="appointment_date" value="{{ old('appointment_date') }}">

        <div class="dp-wrap">
            <div class="dp">
                <div class="dp-head">
                    <button type="button" class="dp-nav" id="prev" aria-label="Previous month">‹</button>
                    <strong id="monthLabel"></strong>
                    <button type="button" class="dp-nav" id="next" aria-label="Next month">›</button>
                </div>
                <div class="dp-grid" id="grid"></div>
                <div class="dp-legend">
                    <span><i style="background:#f3f4f6;border:1px solid #c4c9d0"></i>Available</span>
                    <span><i style="background:var(--brand)"></i>Selected</span>
                    <span><i style="background:#e5e7eb"></i>Full / unavailable</span>
                </div>
            </div>

            <div class="summary">
                <div class="muted" style="text-transform:uppercase;font-size:.75rem;letter-spacing:.05em">Your appointment</div>
                <div class="big" id="sumDate">No date selected</div>
                <div id="sumSlots" class="muted">Pick an available day on the calendar.</div>
                <div class="meter" id="meter" style="display:none"><div id="meterFill"></div></div>
                <p style="margin-top:1.2rem">
                    <button class="btn" id="confirmBtn" disabled>Confirm appointment</button>
                    <a class="btn ghost" href="{{ route('student.dashboard') }}">Cancel</a>
                </p>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const avail = @json(collect($days)->pluck('remaining', 'date'));   // { "2026-11-18": 50, ... }
    const TOTAL = {{ config('cics.daily_slots') }};
    const keys = Object.keys(avail).sort();
    if (!keys.length) { document.getElementById('grid').textContent = 'No dates are open right now.'; return; }

    const parse = k => { const [y, m, d] = k.split('-').map(Number); return new Date(y, m - 1, d); };
    const fmt = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const first = parse(keys[0]), last = parse(keys[keys.length - 1]);
    const minMonth = new Date(first.getFullYear(), first.getMonth(), 1);
    const maxMonth = new Date(last.getFullYear(), last.getMonth(), 1);

    const input = document.getElementById('appointment_date');
    let selected = input.value && avail[input.value] > 0 ? input.value : '';
    let view = selected ? parse(selected) : first;
    view = new Date(view.getFullYear(), view.getMonth(), 1);

    const grid = document.getElementById('grid');
    const label = document.getElementById('monthLabel');
    const prev = document.getElementById('prev'), next = document.getElementById('next');
    const todayKey = fmt(new Date());

    function render() {
        label.textContent = view.toLocaleString('en-US', { month: 'long', year: 'numeric' });
        prev.disabled = view <= minMonth;
        next.disabled = view >= maxMonth;
        grid.innerHTML = '';

        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(n => {
            const h = document.createElement('div'); h.className = 'dp-dow'; h.textContent = n; grid.appendChild(h);
        });
        for (let i = 0; i < view.getDay(); i++) { const b = document.createElement('div'); b.className = 'dp-blank'; grid.appendChild(b); }

        const days = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        for (let n = 1; n <= days; n++) {
            const date = new Date(view.getFullYear(), view.getMonth(), n), key = fmt(date);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'dp-day' + (key === todayKey ? ' today' : '');
            btn.innerHTML = '<span>' + n + '</span>';

            if (key in avail) {
                const left = avail[key];
                if (left < 1) { btn.disabled = true; btn.classList.add('full'); btn.innerHTML += '<small>Full</small>'; }
                else {
                    btn.innerHTML += '<small>' + left + ' left</small>';
                    if (left <= 10) btn.classList.add('low');
                    if (key === selected) btn.classList.add('sel');
                    btn.addEventListener('click', () => choose(key));
                }
            } else { btn.disabled = true; }
            grid.appendChild(btn);
        }
    }

    function choose(key) {
        selected = key; input.value = key; render(); summary();
    }

    function summary() {
        const btn = document.getElementById('confirmBtn');
        if (!selected) { return; }
        const left = avail[selected];
        document.getElementById('sumDate').textContent = parse(selected).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        document.getElementById('sumSlots').textContent = left + ' of ' + TOTAL + ' slots left';
        document.getElementById('meter').style.display = '';
        document.getElementById('meterFill').style.width = Math.round(((TOTAL - left) / TOTAL) * 100) + '%';
        btn.disabled = false;
    }

    prev.addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); render(); });
    next.addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); render(); });

    render(); summary();
})();
</script>
@endsection
