<x-app-layout>
    <x-slot name="title">{{ $group->name }}</x-slot>

    @php $me = Auth::user(); $myBalance = $balances[$me->id] ?? 0; @endphp

    {{-- Header --}}
    <a href="{{ route('dashboard') }}" class="text-sm text-zinc-400 hover:text-white">← Back to dashboard</a>

    <div class="mb-8 mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-white">{{ $group->name }}</h1>
            <p class="mt-1 text-sm text-zinc-400">
                {{ $members->count() }} members · {{ $group->expenses->count() }} expenses · {{ rm($totalSpent) }} spent · created by {{ $group->creator->displayName() }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="rounded-xl border px-4 py-2 text-right {{ $myBalance > 0 ? 'border-emerald-500/30 bg-emerald-500/10' : ($myBalance < 0 ? 'border-red-500/30 bg-red-500/10' : 'border-zinc-800 bg-zinc-900') }}">
                <p class="text-xs text-zinc-400">{{ $myBalance > 0 ? 'You are owed' : ($myBalance < 0 ? 'You owe' : 'Your balance') }}</p>
                <p class="text-lg font-bold {{ $myBalance > 0 ? 'text-emerald-400' : ($myBalance < 0 ? 'text-red-400' : 'text-white') }}">{{ $myBalance === 0 ? 'Settled' : rm($myBalance) }}</p>
            </div>

            @if ($isOwner)
                <x-secondary-button x-data @click="$dispatch('open-modal', 'group-settings')" title="Group settings">⚙ Settings</x-secondary-button>
            @else
                <form method="POST" action="{{ route('members.destroy', [$group, $me]) }}" onsubmit="return confirm('Leave this group?')">
                    @csrf @method('DELETE')
                    <x-secondary-button type="submit">Leave group</x-secondary-button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Left: add + list expenses --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <h2 class="mb-5 text-lg font-semibold text-white">Add an expense</h2>
                @include('expenses._form', [
                    'expense' => null,
                    'action' => route('expenses.store', $group),
                    'method' => 'POST',
                    'submitLabel' => 'Add expense',
                ])
            </x-card>

            <x-card>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-white">Expenses</h2>
                    <span class="text-sm text-zinc-500">{{ rm($totalSpent) }} total</span>
                </div>

                @forelse ($group->expenses as $expense)
                    <div x-data="{ open: false }" class="border-t border-zinc-800 py-4 first:border-t-0 first:pt-0">
                        <div class="flex items-start justify-between gap-4">
                            <button type="button" @click="open = !open" class="flex flex-1 items-start gap-3 text-left">
                                <span class="mt-0.5 flex h-10 w-12 shrink-0 flex-col items-center justify-center rounded-lg bg-zinc-800 text-[10px] uppercase leading-tight text-zinc-400">
                                    <span>{{ $expense->expense_date->format('M') }}</span>
                                    <span class="text-sm font-bold text-white">{{ $expense->expense_date->format('d') }}</span>
                                </span>
                                <span>
                                    <span class="block font-medium text-white">{{ $expense->description }}</span>
                                    <span class="block text-sm text-zinc-400">
                                        {{ $expense->payer->id === $me->id ? 'You' : $expense->payer->displayName() }} paid
                                        · {{ $expense->split_type === 'equal' ? 'split equally' : 'custom split' }} between {{ $expense->splits->count() }}
                                    </span>
                                </span>
                            </button>
                            <div class="text-right">
                                <p class="font-semibold text-white">{{ rm(\App\Services\BalanceCalculator::toSen($expense->amount)) }}</p>
                                @php $myShare = $expense->splits->firstWhere('user_id', $me->id); @endphp
                                <p class="text-xs text-zinc-500">{{ $myShare ? 'Your share '.rm(\App\Services\BalanceCalculator::toSen($myShare->amount)) : 'Not involved' }}</p>
                            </div>
                        </div>

                        <div x-show="open" x-transition x-cloak class="mt-3 rounded-xl bg-zinc-950/60 p-3 sm:ml-[60px]">
                            <ul class="space-y-1 text-sm">
                                @foreach ($expense->splits as $split)
                                    <li class="flex justify-between text-zinc-300">
                                        <span>{{ $split->user->displayName() }}</span>
                                        <span>{{ rm(\App\Services\BalanceCalculator::toSen($split->amount)) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-3 flex justify-end gap-2 border-t border-zinc-800 pt-3">
                                <a href="{{ route('expenses.edit', $expense) }}" class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-200 hover:bg-zinc-800">Edit</a>
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense? Balances will be recalculated.')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-medium text-red-300 hover:bg-red-500/10">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-zinc-500">No expenses yet. Add the first one above.</p>
                @endforelse
            </x-card>
        </div>

        {{-- Right: balances, settle up, members, history --}}
        <div class="space-y-6">
            <x-card>
                <h2 class="mb-4 text-lg font-semibold text-white">Balances</h2>
                <ul class="space-y-3">
                    @foreach ($members as $member)
                        @php $b = $balances[$member->id] ?? 0; @endphp
                        <li class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-3">
                                <x-avatar :user="$member" />
                                <span class="text-sm text-zinc-200">{{ $member->displayName() }}{{ $member->id === $me->id ? ' (you)' : '' }}</span>
                            </span>
                            <span class="text-right text-sm font-semibold {{ $b > 0 ? 'text-emerald-400' : ($b < 0 ? 'text-red-400' : 'text-zinc-500') }}">
                                {{ $b > 0 ? 'gets back '.rm($b) : ($b < 0 ? 'owes '.rm($b) : 'settled') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card>
                <h2 class="text-lg font-semibold text-white">Settle up</h2>
                <p class="mb-4 mt-1 text-xs text-zinc-500">Fewest transfers needed to clear every debt in this group.</p>

                @forelse ($suggested as $t)
                    @php $from = $members[$t['from']]; $to = $members[$t['to']]; @endphp
                    <div class="mb-3 rounded-xl border border-zinc-800 bg-zinc-950/60 p-3">
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="text-zinc-200"><b class="text-white">{{ $from->id === $me->id ? 'You' : $from->displayName() }}</b> → <b class="text-white">{{ $to->id === $me->id ? 'You' : $to->displayName() }}</b></span>
                            <span class="font-semibold text-amber-300">{{ rm($t['amount']) }}</span>
                        </div>
                        <form method="POST" action="{{ route('settlements.store', $group) }}" class="mt-3"
                              onsubmit="return confirm('Record that {{ e($from->displayName()) }} paid {{ e($to->displayName()) }} {{ rm($t['amount']) }}?')">
                            @csrf
                            <input type="hidden" name="from_user" value="{{ $t['from'] }}">
                            <input type="hidden" name="to_user" value="{{ $t['to'] }}">
                            <input type="hidden" name="amount" value="{{ \App\Services\BalanceCalculator::toRinggit($t['amount']) }}">
                            <button class="w-full rounded-lg bg-emerald-500/15 py-2 text-xs font-semibold text-emerald-300 transition hover:bg-emerald-500/25">Mark as paid</button>
                        </form>
                    </div>
                @empty
                    <p class="rounded-xl bg-emerald-500/10 px-4 py-3 text-center text-sm text-emerald-200">🎉 Everyone is settled up.</p>
                @endforelse
            </x-card>

            <x-card>
                <h2 class="mb-4 text-lg font-semibold text-white">Members</h2>
                <ul class="space-y-3">
                    @foreach ($members as $member)
                        <li class="flex items-center justify-between gap-3">
                            <span class="flex min-w-0 items-center gap-3">
                                <x-avatar :user="$member" />
                                <span class="min-w-0">
                                    <span class="block truncate text-sm text-zinc-200">{{ $member->displayName() }}
                                        @if ($member->id === (int) $group->created_by)<span class="ml-1 rounded bg-zinc-800 px-1.5 py-0.5 text-[10px] text-zinc-400">creator</span>@endif
                                    </span>
                                    <span class="block truncate text-xs text-zinc-500">{{ $member->email }}</span>
                                </span>
                            </span>
                            @if ($isOwner && $member->id !== (int) $group->created_by)
                                <form method="POST" action="{{ route('members.destroy', [$group, $member]) }}" onsubmit="return confirm('Remove {{ e($member->displayName()) }} from the group?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg px-2 py-1 text-xs text-zinc-500 hover:bg-red-500/10 hover:text-red-300" title="Remove member">Remove</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('members.store', $group) }}" class="mt-5 border-t border-zinc-800 pt-4">
                    @csrf
                    <x-input-label for="email" value="Add a friend by email" />
                    <div class="mt-1 flex gap-2">
                        <x-text-input id="email" name="email" type="email" class="block w-full min-w-0 text-sm" placeholder="friend@email.com" :value="old('email')" required />
                        <x-primary-button class="shrink-0">Add</x-primary-button>
                    </div>
                    <x-input-error :messages="$errors->member->get('email')" class="mt-2" />
                </form>
            </x-card>

            <x-card>
                <h2 class="mb-4 text-lg font-semibold text-white">Payment history</h2>
                @forelse ($group->settlements as $s)
                    <div class="flex items-center justify-between gap-3 border-t border-zinc-800 py-3 text-sm first:border-t-0 first:pt-0">
                        <div>
                            <p class="text-zinc-200">{{ $s->payer->displayName() }} paid {{ $s->receiver->displayName() }}</p>
                            <p class="text-xs text-zinc-500">{{ $s->settled_at->format('d M Y, g:i A') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-emerald-400">{{ rm(\App\Services\BalanceCalculator::toSen($s->amount)) }}</span>
                            <form method="POST" action="{{ route('settlements.destroy', $s) }}" onsubmit="return confirm('Undo this payment record?')">
                                @csrf @method('DELETE')
                                <button class="rounded px-1.5 text-zinc-500 hover:text-red-300" title="Undo payment">↺</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No payments recorded yet.</p>
                @endforelse
            </x-card>
        </div>
    </div>

    @if ($isOwner)
        <x-modal name="group-settings" :show="$errors->has('name')" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-white">Group settings</h2>

                <form method="POST" action="{{ route('groups.update', $group) }}" class="mt-5">
                    @csrf @method('PATCH')
                    <x-input-label for="group_name" value="Group name" />
                    <div class="mt-1 flex gap-2">
                        <x-text-input id="group_name" name="name" class="block w-full" maxlength="80" required :value="old('name', $group->name)" />
                        <x-primary-button class="shrink-0">Save</x-primary-button>
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </form>

                <div class="mt-6 rounded-xl border border-red-500/30 bg-red-500/5 p-4">
                    <h3 class="font-semibold text-red-300">Delete group</h3>
                    <p class="mt-1 text-sm text-zinc-400">This permanently deletes the group with all its expenses and payment records.</p>
                    <form method="POST" action="{{ route('groups.destroy', $group) }}" class="mt-3"
                          onsubmit="return confirm('Delete {{ e($group->name) }} and everything in it? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <x-danger-button>Delete this group</x-danger-button>
                    </form>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">Close</x-secondary-button>
                </div>
            </div>
        </x-modal>
    @endif
</x-app-layout>
