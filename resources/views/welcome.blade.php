<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="SplitMate helps housemates and friends split shared bills and settle up with the fewest transfers.">
        <title>SplitMate · Split bills with friends</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        {{-- Alpine components for the landing page. Defined before Alpine starts (Vite modules load deferred). --}}
        <script>
            const PEOPLE = [
                { name: 'Hisyam', initials: 'HR', tone: 'bg-sky-500/20 text-sky-300 ring-sky-400/40', bar: 'bg-sky-400' },
                { name: 'Aina', initials: 'AS', tone: 'bg-amber-500/20 text-amber-300 ring-amber-400/40', bar: 'bg-amber-400' },
                { name: 'Ben', initials: 'BT', tone: 'bg-fuchsia-500/20 text-fuchsia-300 ring-fuchsia-400/40', bar: 'bg-fuchsia-400' },
                { name: 'Chong', initials: 'CW', tone: 'bg-rose-500/20 text-rose-300 ring-rose-400/40', bar: 'bg-rose-400' },
            ];
            const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const rm = sen => 'RM' + (sen / 100).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            /* Hero: a group feed where new activity slides in. */
            function activityFeed() {
                const events = [
                    { who: 0, text: 'added Electricity bill (TNB)', amount: 'RM120.00', kind: 'expense' },
                    { who: 2, text: 'paid Hisyam', amount: 'RM18.75', kind: 'payment' },
                    { who: 1, text: 'added Groceries at Lotus’s', amount: 'RM54.00', kind: 'expense' },
                    { who: 3, text: 'added Dinner at Nasi Kandar', amount: 'RM86.00', kind: 'expense' },
                    { who: 1, text: 'paid Ben', amount: 'RM20.00', kind: 'payment' },
                    { who: 2, text: 'added Unifi Wi-Fi', amount: 'RM99.00', kind: 'expense' },
                ];
                return {
                    people: PEOPLE,
                    items: [],
                    next: 0,
                    uid: 0,
                    paused: false,
                    init() {
                        for (let i = 0; i < 4; i++) this.push();
                        if (reduceMotion()) return;
                        setInterval(() => { if (!this.paused && !document.hidden) this.push(); }, 2800);
                    },
                    push() {
                        const e = events[this.next++ % events.length];
                        this.items.unshift({ ...e, id: this.uid++ });
                        this.items = this.items.slice(0, 4);
                    },
                };
            }

            /* "Split a bill" demo: equal split, leftover sen go to the first people ticked (same rule as the app). */
            function splitDemo() {
                return {
                    people: PEOPLE,
                    amount: '86.00',
                    payer: 3,
                    included: [0, 1, 2, 3],
                    presets: [['🍛 Makan', '86.00'], ['🛒 Groceries', '54.00'], ['📶 Wi-Fi', '99.00'], ['⛽ Petrol', '95.40']],
                    get totalSen() { const v = parseFloat(this.amount); return isNaN(v) || v < 0 ? 0 : Math.round(v * 100); },
                    toggle(i) {
                        this.included = this.included.includes(i) ? this.included.filter(x => x !== i) : [...this.included, i].sort();
                    },
                    shares() {
                        const n = this.included.length;
                        if (!n) return {};
                        const base = Math.floor(this.totalSen / n), rem = this.totalSen - base * n;
                        return Object.fromEntries(this.included.map((id, k) => [id, base + (k < rem ? 1 : 0)]));
                    },
                    rows() {
                        const s = this.shares(), payer = this.people[this.payer].name;
                        return this.people.map((p, i) => {
                            const share = s[i] ?? 0;
                            let line, tone;
                            if (i === this.payer) {
                                const back = this.totalSen - share;
                                line = back > 0 ? 'gets back ' + rm(back) : 'paid only for themself';
                                tone = 'text-emerald-400';
                            } else if (share > 0) {
                                line = 'owes ' + payer + ' ' + rm(share);
                                tone = 'text-red-300';
                            } else {
                                line = 'not sharing';
                                tone = 'text-zinc-500';
                            }
                            return { ...p, i, share, line, tone, pct: this.totalSen ? (share / this.totalSen) * 100 : 0 };
                        });
                    },
                    rm,
                };
            }

            /* "Simplify debts" demo: same greedy algorithm as BalanceCalculator::settlements(). */
            function debtDemo() {
                const POS = [{ x: 80, y: 62 }, { x: 320, y: 62 }, { x: 320, y: 238 }, { x: 80, y: 238 }];
                const R = 30;
                return {
                    people: PEOPLE,
                    mode: 'before',
                    debts: [
                        { from: 1, to: 0, amount: 3000 }, { from: 2, to: 0, amount: 3000 }, { from: 3, to: 1, amount: 2000 },
                        { from: 0, to: 2, amount: 2500 }, { from: 3, to: 2, amount: 2500 }, { from: 1, to: 3, amount: 1500 },
                    ],
                    version: 0,
                    net() {
                        const b = [0, 0, 0, 0];
                        for (const d of this.debts) { b[d.from] -= d.amount; b[d.to] += d.amount; }
                        return b;
                    },
                    simplified() {
                        const b = this.net();
                        const debtors = b.map((v, i) => ({ i, a: -v })).filter(x => x.a > 0).sort((x, y) => y.a - x.a);
                        const creditors = b.map((v, i) => ({ i, a: v })).filter(x => x.a > 0).sort((x, y) => y.a - x.a);
                        const out = [];
                        let d = 0, c = 0;
                        while (d < debtors.length && c < creditors.length) {
                            const a = Math.min(debtors[d].a, creditors[c].a);
                            out.push({ from: debtors[d].i, to: creditors[c].i, amount: a });
                            debtors[d].a -= a; creditors[c].a -= a;
                            if (!debtors[d].a) d++;
                            if (!creditors[c].a) c++;
                        }
                        return out;
                    },
                    edges() { return this.mode === 'before' ? this.debts : this.simplified(); },
                    setMode(m) { if (this.mode !== m) { this.mode = m; this.version++; } },
                    shuffle() {
                        const pairs = [];
                        for (let a = 0; a < 4; a++) for (let b = 0; b < 4; b++) if (a !== b) pairs.push([a, b]);
                        let debts;
                        do {
                            pairs.sort(() => Math.random() - 0.5);
                            const chosen = [];
                            for (const [a, b] of pairs) {
                                if (chosen.length >= 5 + Math.floor(Math.random() * 2)) break;
                                if (chosen.some(([x, y]) => (x === a && y === b) || (x === b && y === a))) continue;
                                chosen.push([a, b]);
                            }
                            debts = chosen.map(([from, to]) => ({ from, to, amount: (2 + Math.floor(Math.random() * 11)) * 500 }));
                            this.debts = debts;
                        } while (this.simplified().length >= debts.length - 1 || this.simplified().length === 0);
                        this.mode = 'before';
                        this.version++;
                    },
                    svg() {
                        void this.version;
                        const after = this.mode === 'after';
                        const color = after ? '#34d399' : '#fbbf24';
                        let s = `<defs><marker id="dm-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="${color}"/></marker></defs>`;
                        this.edges().forEach((e, k) => {
                            const p = POS[e.from], q = POS[e.to];
                            const dx = q.x - p.x, dy = q.y - p.y, len = Math.hypot(dx, dy), ux = dx / len, uy = dy / len;
                            const sx = p.x + ux * (R + 4), sy = p.y + uy * (R + 4), ex = q.x - ux * (R + 8), ey = q.y - uy * (R + 8);
                            const bend = after ? 0 : 26;
                            const cx = (sx + ex) / 2 - uy * bend, cy = (sy + ey) / 2 + ux * bend;
                            const lx = 0.25 * sx + 0.5 * cx + 0.25 * ex, ly = 0.25 * sy + 0.5 * cy + 0.25 * ey;
                            const label = rm(e.amount).replace('.00', '');
                            const w = label.length * 7.4 + 14;
                            s += `<g class="dm-edge" style="--d:${k * 120}ms">`
                                + `<path d="M${sx} ${sy} Q${cx} ${cy} ${ex} ${ey}" fill="none" stroke="${color}" stroke-width="${after ? 3 : 2}" stroke-linecap="round" marker-end="url(#dm-arrow)" pathLength="1" class="dm-draw"/>`
                                + `<g class="dm-pop"><rect x="${lx - w / 2}" y="${ly - 11}" width="${w}" height="22" rx="11" fill="#18181b" stroke="${color}" stroke-opacity=".5"/>`
                                + `<text x="${lx}" y="${ly + 4.5}" text-anchor="middle" font-size="12.5" font-weight="600" fill="${color}">${label}</text></g></g>`;
                        });
                        const net = this.net();
                        POS.forEach((p, i) => {
                            const v = net[i], below = p.y > 150;
                            const tag = v > 0 ? '+' + rm(v).replace('.00', '') : v < 0 ? '−' + rm(-v).replace('.00', '') : 'settled';
                            const tagColor = v > 0 ? '#34d399' : v < 0 ? '#f87171' : '#a1a1aa';
                            s += `<circle cx="${p.x}" cy="${p.y}" r="${R}" fill="#27272a" stroke="#3f3f46" stroke-width="2"/>`
                                + `<text x="${p.x}" y="${p.y + 5}" text-anchor="middle" font-size="15" font-weight="700" fill="#fafafa">${this.people[i].name}</text>`
                                + `<text x="${p.x}" y="${below ? p.y + R + 20 : p.y - R - 10}" text-anchor="middle" font-size="12" font-weight="600" fill="${tagColor}">${tag}</text>`;
                        });
                        return s;
                    },
                    rm,
                };
            }

            /* Fade sections in as they scroll into view. */
            document.addEventListener('DOMContentLoaded', () => {
                const els = document.querySelectorAll('[data-reveal]');
                if (reduceMotion() || !('IntersectionObserver' in window)) { els.forEach(el => el.classList.add('is-visible')); return; }
                const io = new IntersectionObserver(entries => entries.forEach(en => {
                    if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
                }), { threshold: 0.12 });
                els.forEach(el => io.observe(el));
            });
        </script>

        <style>
            [data-reveal] { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s ease; }
            [data-reveal].is-visible { opacity: 1; transform: none; }
            .dm-draw { stroke-dasharray: 1; stroke-dashoffset: 1; animation: dm-draw .6s ease-out forwards; animation-delay: var(--d); }
            .dm-pop { opacity: 0; animation: dm-pop .35s ease-out forwards; animation-delay: calc(var(--d) + .35s); }
            .dm-edge { --d: 0ms; }
            @keyframes dm-draw { to { stroke-dashoffset: 0; } }
            @keyframes dm-pop { to { opacity: 1; } }
            @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
            .float { animation: float 6s ease-in-out infinite; }
            @media (prefers-reduced-motion: reduce) {
                [data-reveal] { opacity: 1; transform: none; transition: none; }
                .dm-draw { animation: none; stroke-dashoffset: 0; }
                .dm-pop { animation: none; opacity: 1; }
                .float { animation: none; }
            }
            input[type=range] { accent-color: #10b981; }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="overflow-x-hidden bg-zinc-950 font-sans text-zinc-100 antialiased">
        <div class="pointer-events-none fixed left-1/2 top-[-160px] h-[560px] w-[560px] -translate-x-1/2 rounded-full bg-emerald-500/10 blur-[120px]"></div>

        <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            {{-- Top bar --}}
            <header class="flex h-16 items-center justify-between sm:h-20">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <x-application-logo class="h-9 w-9" />
                    <span class="text-xl font-bold tracking-tight text-white">SplitMate</span>
                </a>
                <nav class="flex items-center gap-1 sm:gap-2">
                    <a href="#try" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-300 hover:text-white md:inline-block">Try it</a>
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-300 hover:text-white sm:px-4">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-emerald-500 px-3 py-2.5 text-sm font-semibold text-zinc-950 hover:bg-emerald-400 sm:px-4">Sign up</a>
                </nav>
            </header>

            {{-- Hero --}}
            <section class="grid grid-cols-1 items-center gap-12 py-12 sm:py-20 lg:grid-cols-[1.1fr_1fr] lg:gap-16">
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                        For housemates, trips and group projects
                    </span>
                    <h1 class="mt-6 text-4xl font-extrabold leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Split bills with friends.<br class="hidden sm:block">
                        <span class="text-emerald-400">No more awkward money talks.</span>
                    </h1>
                    <p class="mx-auto mt-6 max-w-xl text-base text-zinc-400 sm:text-lg lg:mx-0">
                        Record shared expenses, see who owes whom at a glance, and settle up with the fewest transfers possible.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                        <a href="{{ route('register') }}" class="rounded-lg bg-emerald-500 px-6 py-3.5 font-semibold text-zinc-950 transition hover:bg-emerald-400">Get started free</a>
                        <a href="#try" class="rounded-lg border border-zinc-700 px-6 py-3.5 font-semibold text-zinc-200 transition hover:bg-zinc-900">Try the demo ↓</a>
                    </div>
                    <dl class="mx-auto mt-10 grid max-w-md grid-cols-3 gap-4 text-center lg:mx-0 lg:text-left">
                        <div><dt class="text-xs text-zinc-500">Transfers</dt><dd class="text-lg font-bold text-white">Fewest</dd></div>
                        <div><dt class="text-xs text-zinc-500">Split preview</dt><dd class="text-lg font-bold text-white">Live</dd></div>
                        <div><dt class="text-xs text-zinc-500">Price</dt><dd class="text-lg font-bold text-white">Free</dd></div>
                    </dl>
                </div>

                {{-- Live activity card --}}
                <div x-data="activityFeed()" @mouseenter="paused = true" @mouseleave="paused = false" class="float mx-auto w-full max-w-md">
                    <div class="rounded-3xl border border-zinc-800 bg-zinc-900/80 p-5 shadow-2xl shadow-emerald-950/40 backdrop-blur sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-zinc-500">Group</p>
                                <p class="font-semibold text-white">Rumah Sewa Seksyen 7</p>
                            </div>
                            <div class="flex -space-x-2">
                                <template x-for="p in people" :key="p.initials">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-[11px] font-semibold ring-2 ring-zinc-900" :class="p.tone" x-text="p.initials"></span>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-red-500/10 p-3"><p class="text-xs text-zinc-400">You owe</p><p class="text-xl font-bold text-red-400">RM2.75</p></div>
                            <div class="rounded-2xl bg-emerald-500/10 p-3"><p class="text-xs text-zinc-400">Ben gets back</p><p class="text-xl font-bold text-emerald-400">RM26.25</p></div>
                        </div>

                        <p class="mb-2 mt-5 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-zinc-500">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span> Live activity
                        </p>
                        <ul class="relative h-[232px] overflow-hidden [mask-image:linear-gradient(to_bottom,black_75%,transparent)]" aria-live="polite">
                            <template x-for="item in items" :key="item.id">
                                <li x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 -translate-y-4"
                                    class="mb-2 flex items-center gap-3 rounded-xl border border-zinc-800 bg-zinc-950/60 px-3 py-2.5">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold" :class="people[item.who].tone" x-text="people[item.who].initials"></span>
                                    <p class="min-w-0 flex-1 truncate text-sm text-zinc-300"><b class="text-white" x-text="people[item.who].name"></b> <span x-text="item.text"></span></p>
                                    <span class="shrink-0 text-sm font-semibold" :class="item.kind === 'payment' ? 'text-emerald-400' : 'text-white'" x-text="item.amount"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Interactive demos --}}
            <section id="try" class="scroll-mt-6 py-12 sm:py-16">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-emerald-400">Try it, no account needed</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">See how SplitMate does the maths</h2>
                    <p class="mt-3 text-zinc-400">Tap, type and play. This is the same logic the app uses.</p>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {{-- Split a bill --}}
                    <div data-reveal x-data="splitDemo()" class="rounded-3xl border border-zinc-800 bg-zinc-900/60 p-5 sm:p-7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-lg">🧾</span>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Split a bill</h3>
                                <p class="text-sm text-zinc-400">Change anything and the shares update instantly.</p>
                            </div>
                        </div>

                        <label for="demo-amount" class="mt-6 block text-sm font-medium text-zinc-300">Bill amount</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-zinc-500">RM</span>
                            <input id="demo-amount" type="number" inputmode="decimal" min="0" step="0.01" x-model="amount"
                                   class="block w-full rounded-xl border-zinc-700 bg-zinc-950 py-3 pl-12 text-2xl font-bold text-white focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-for="[label, value] in presets" :key="label">
                                <button type="button" @click="amount = value"
                                        :class="amount === value ? 'border-emerald-500 bg-emerald-500/15 text-emerald-200' : 'border-zinc-700 text-zinc-300 hover:border-zinc-500'"
                                        class="min-h-[40px] rounded-full border px-3.5 text-sm transition" x-text="label"></button>
                            </template>
                        </div>

                        <p class="mt-6 text-sm font-medium text-zinc-300">Who paid?</p>
                        <div class="mt-2 grid grid-cols-4 gap-2">
                            <template x-for="(p, i) in people" :key="p.name">
                                <button type="button" @click="payer = i" :aria-pressed="payer === i"
                                        :class="payer === i ? 'border-emerald-500 bg-emerald-500/15 text-white' : 'border-zinc-800 text-zinc-400 hover:border-zinc-600'"
                                        class="min-h-[44px] rounded-xl border px-1 text-sm font-medium transition" x-text="p.name"></button>
                            </template>
                        </div>

                        <p class="mt-6 text-sm font-medium text-zinc-300">Who’s sharing? <span class="font-normal text-zinc-500">Tap to include or leave out</span></p>
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            <template x-for="(p, i) in people" :key="p.name">
                                <button type="button" @click="toggle(i)" :aria-pressed="included.includes(i)"
                                        class="group flex flex-col items-center gap-1.5 rounded-xl py-2 transition active:scale-95">
                                    <span class="relative inline-flex h-12 w-12 items-center justify-center rounded-full text-sm font-bold ring-2 transition"
                                          :class="included.includes(i) ? p.tone : 'bg-zinc-800 text-zinc-600 ring-zinc-800 opacity-60'">
                                        <span x-text="p.initials"></span>
                                        <span x-show="included.includes(i)" class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[11px] text-zinc-950">✓</span>
                                    </span>
                                    <span class="text-xs" :class="included.includes(i) ? 'text-zinc-200' : 'text-zinc-600'" x-text="p.name"></span>
                                </button>
                            </template>
                        </div>

                        <ul class="mt-6 space-y-3 border-t border-zinc-800 pt-5">
                            <template x-for="r in rows()" :key="r.name">
                                <li>
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="text-zinc-200"><b x-text="r.name"></b> <span :class="r.tone" x-text="r.line"></span></span>
                                        <span class="shrink-0 font-semibold text-white" x-text="r.share ? rm(r.share) : '—'"></span>
                                    </div>
                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-800">
                                        <div class="h-full rounded-full transition-all duration-500 ease-out" :class="r.bar" :style="`width:${r.pct}%`"></div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        <p class="mt-4 text-xs text-zinc-500" x-show="included.length && totalSen % included.length" x-cloak>
                            The leftover sen go to the first people, so the shares always add up exactly to the bill.
                        </p>
                        <p class="mt-4 rounded-xl bg-amber-500/10 px-3 py-2 text-sm text-amber-200" x-show="!included.length" x-cloak>Pick at least one person to share the bill.</p>
                    </div>

                    {{-- Simplify debts --}}
                    <div data-reveal x-data="debtDemo()" class="flex flex-col rounded-3xl border border-zinc-800 bg-zinc-900/60 p-5 sm:p-7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-lg">🔀</span>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Simplify debts</h3>
                                <p class="text-sm text-zinc-400">Fewer bank transfers, same result.</p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-1 rounded-xl bg-zinc-950 p-1" role="group" aria-label="Compare">
                            <button type="button" @click="setMode('before')" :aria-pressed="mode === 'before'"
                                    :class="mode === 'before' ? 'bg-amber-500/15 text-amber-200' : 'text-zinc-400 hover:text-white'"
                                    class="min-h-[44px] rounded-lg text-sm font-semibold transition">Without SplitMate</button>
                            <button type="button" @click="setMode('after')" :aria-pressed="mode === 'after'"
                                    :class="mode === 'after' ? 'bg-emerald-500/15 text-emerald-200' : 'text-zinc-400 hover:text-white'"
                                    class="min-h-[44px] rounded-lg text-sm font-semibold transition">With SplitMate</button>
                        </div>

                        <div class="relative mt-4 flex-1 rounded-2xl border border-zinc-800 bg-zinc-950/60">
                            <svg viewBox="0 0 400 300" class="block h-auto w-full touch-manipulation select-none" role="img"
                                 :aria-label="(mode === 'before' ? 'Debts before simplifying: ' : 'Debts after simplifying: ') + edges().length + ' transfers'"
                                 font-family="Geist, ui-sans-serif, system-ui, sans-serif" x-html="svg()"></svg>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-zinc-300">
                                <span class="text-2xl font-bold" :class="mode === 'before' ? 'text-amber-300' : 'text-emerald-300'" x-text="edges().length"></span>
                                transfers
                                <span class="text-zinc-500" x-show="mode === 'after'" x-cloak>instead of <b class="text-zinc-300" x-text="debts.length"></b></span>
                            </p>
                            <button type="button" @click="shuffle()" class="min-h-[44px] rounded-lg border border-zinc-700 px-4 text-sm font-semibold text-zinc-200 transition hover:bg-zinc-800 active:scale-95">🎲 Shuffle</button>
                        </div>

                        <ul class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4 text-sm">
                            <template x-for="(e, k) in edges()" :key="mode + version + '-' + k">
                                <li class="flex justify-between text-zinc-300">
                                    <span><b class="text-white" x-text="people[e.from].name"></b> pays <b class="text-white" x-text="people[e.to].name"></b></span>
                                    <span class="font-semibold" :class="mode === 'before' ? 'text-amber-300' : 'text-emerald-300'" x-text="rm(e.amount)"></span>
                                </li>
                            </template>
                        </ul>
                        <p class="mt-4 text-xs text-zinc-500">Everyone ends up with exactly the same balance either way. SplitMate matches the biggest debtor with the biggest creditor until everyone is settled.</p>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section class="py-12 sm:py-16">
                <h2 data-reveal class="text-center text-3xl font-bold tracking-tight text-white">Three steps to settled</h2>
                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['👥', '1. Create a group', 'Make a group for your house or trip and add friends by their email.'],
                        ['🧾', '2. Add expenses', 'Log who paid and split it equally or by custom amounts. Shares are shown before you save.'],
                        ['✅', '3. Settle up', 'SplitMate works out the fewest payments to clear all debts, then tracks who has paid.'],
                    ] as $k => [$icon, $title, $text])
                        <div data-reveal style="transition-delay: {{ $k * 120 }}ms" class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 transition hover:-translate-y-1 hover:border-emerald-500/40">
                            <p class="text-3xl">{{ $icon }}</p>
                            <h3 class="mt-3 font-semibold text-white">{{ $title }}</h3>
                            <p class="mt-1 text-sm text-zinc-400">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Call to action --}}
            <section data-reveal class="my-12 rounded-3xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/15 to-zinc-900 px-6 py-12 text-center sm:my-16 sm:px-12">
                <h2 class="text-3xl font-bold tracking-tight text-white">Bayar hutang member, baru tak diungkit.</h2>
                <p class="mx-auto mt-3 max-w-lg text-zinc-300">Pay your friends back and nobody has to bring it up again. Start your first group in under a minute.</p>
                <a href="{{ route('register') }}" class="mt-8 inline-block rounded-lg bg-emerald-500 px-8 py-3.5 font-semibold text-zinc-950 transition hover:bg-emerald-400">Create a free account</a>
            </section>

            <footer class="border-t border-zinc-800 py-8 text-center text-sm text-zinc-500">
                SplitMate · ITT626 Back-End Technology project · Built with Laravel
            </footer>
        </div>
    </body>
</html>
