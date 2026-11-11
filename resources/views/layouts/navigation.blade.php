<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <x-application-logo class="h-8 w-8" />
            <span class="text-lg font-bold tracking-tight text-white">SplitMate</span>
        </a>

        <div class="hidden items-center gap-1 sm:flex">
            <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard', 'groups.*', 'expenses.*') ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white' }}">Dashboard</a>
            <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('profile.*') ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white' }}">Account</a>

            <div class="ms-3 flex items-center gap-3 border-s border-zinc-800 ps-4">
                <x-avatar :user="Auth::user()" />
                <span class="text-sm text-zinc-300">{{ Auth::user()->displayName() }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-zinc-800 px-3 py-1.5 text-sm text-zinc-400 transition hover:border-zinc-700 hover:text-white">Log out</button>
                </form>
            </div>
        </div>

        <button @click="open = ! open" class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-800 hover:text-white sm:hidden" aria-label="Menu">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="border-t border-zinc-800 px-4 pb-4 pt-2 sm:hidden">
        <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-zinc-200 hover:bg-zinc-800">Dashboard</a>
        <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-zinc-200 hover:bg-zinc-800">Account</a>
        <div class="mt-2 border-t border-zinc-800 pt-3 text-sm text-zinc-400">{{ Auth::user()->email }}</div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-zinc-200 hover:bg-zinc-800">Log out</button>
        </form>
    </div>
</nav>
