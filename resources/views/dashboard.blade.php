<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-zinc-400">Welcome back,</p>
            <h1 class="text-3xl font-bold tracking-tight text-white">{{ Auth::user()->displayName() }} 👋</h1>
        </div>
        <button type="button" x-data @click="$dispatch('open-modal', 'create-group')"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400">
            <span class="text-lg leading-none">+</span> New group
        </button>
    </div>

    {{-- Summary across every group --}}
    <div class="mb-10 grid gap-4 sm:grid-cols-3">
        <x-card>
            <p class="text-sm text-zinc-400">Net balance</p>
            <p class="mt-2 text-3xl font-bold {{ $net > 0 ? 'text-emerald-400' : ($net < 0 ? 'text-red-400' : 'text-white') }}">
                {{ $net < 0 ? '−' : '' }}{{ rm($net) }}
            </p>
            <p class="mt-1 text-xs text-zinc-500">{{ $net > 0 ? 'Overall, you are owed money' : ($net < 0 ? 'Overall, you owe money' : 'You are all settled up') }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-zinc-400">You owe</p>
            <p class="mt-2 text-3xl font-bold text-red-400">{{ rm($youOwe) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Across all your groups</p>
        </x-card>
        <x-card>
            <p class="text-sm text-zinc-400">You are owed</p>
            <p class="mt-2 text-3xl font-bold text-emerald-400">{{ rm($youAreOwed) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Across all your groups</p>
        </x-card>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-white">Your groups</h2>
        <span class="text-sm text-zinc-500">{{ $groups->count() }} {{ Str::plural('group', $groups->count()) }}</span>
    </div>

    @if ($groups->isEmpty())
        <x-card class="py-14 text-center">
            <p class="text-4xl">🧾</p>
            <h3 class="mt-3 text-lg font-semibold text-white">No groups yet</h3>
            <p class="mx-auto mt-1 max-w-sm text-sm text-zinc-400">Create a group for your house, trip or project, then add friends by their email.</p>
            <button type="button" x-data @click="$dispatch('open-modal', 'create-group')"
                    class="mt-5 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-zinc-950 hover:bg-emerald-400">Create your first group</button>
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($groups as $group)
                <a href="{{ route('groups.show', $group) }}"
                   class="group rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 transition hover:border-emerald-500/40 hover:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-lg font-semibold text-white group-hover:text-emerald-300">{{ $group->name }}</h3>
                        <span class="text-zinc-600 transition group-hover:translate-x-0.5 group-hover:text-emerald-400">→</span>
                    </div>

                    <div class="mt-3 flex -space-x-2">
                        @foreach ($group->members->take(5) as $member)
                            <x-avatar :user="$member" size="h-8 w-8 text-[11px]" class="ring-2 ring-zinc-900" />
                        @endforeach
                        @if ($group->members->count() > 5)
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-800 text-[11px] text-zinc-300 ring-2 ring-zinc-900">+{{ $group->members->count() - 5 }}</span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-end justify-between border-t border-zinc-800 pt-4">
                        <div>
                            <p class="text-xs text-zinc-500">{{ $group->members->count() }} members · {{ $group->expenses->count() }} expenses</p>
                            <p class="text-xs text-zinc-500">Total spent {{ rm($group->total_spent) }}</p>
                        </div>
                        <div class="text-right">
                            @if ($group->my_balance > 0)
                                <p class="text-xs text-zinc-400">You are owed</p>
                                <p class="font-semibold text-emerald-400">{{ rm($group->my_balance) }}</p>
                            @elseif ($group->my_balance < 0)
                                <p class="text-xs text-zinc-400">You owe</p>
                                <p class="font-semibold text-red-400">{{ rm($group->my_balance) }}</p>
                            @else
                                <p class="text-sm font-medium text-zinc-400">Settled up</p>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <x-modal name="create-group" :show="$errors->has('name')" maxWidth="md" focusable>
        <form method="POST" action="{{ route('groups.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-semibold text-white">Create a new group</h2>
            <p class="mt-1 text-sm text-zinc-400">For example: your rented house, a trip, or a class project.</p>

            <div class="mt-5">
                <x-input-label for="name" value="Group name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="Rumah Sewa Seksyen 7" maxlength="80" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Create group</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
