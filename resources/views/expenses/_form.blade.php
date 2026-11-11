{{--
    Shared add / edit expense form.
    Alpine.js shows each person's share live while the user types,
    so they can see the split before it is saved.
--}}
@php
    $members = $group->members;
    $existingSplits = $expense?->splits->pluck('amount', 'user_id') ?? collect();

    $initial = [
        'amount' => old('amount', $expense ? number_format((float) $expense->amount, 2, '.', '') : ''),
        'splitType' => old('split_type', $expense->split_type ?? 'equal'),
        'participants' => array_map('intval', old('participants', $expense
            ? $existingSplits->keys()->all()
            : $members->pluck('id')->all())),
        'custom' => (object) collect(old('custom', $expense && $expense->split_type === 'custom'
            ? $existingSplits->map(fn ($a) => number_format((float) $a, 2, '.', ''))->all()
            : []))->all(),
        'members' => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->displayName()])->values(),
    ];
@endphp

<form method="POST" action="{{ $action }}" x-data="expenseForm(@js($initial))" class="space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="description" value="What was it for?" />
            <x-text-input id="description" name="description" class="mt-1 block w-full" maxlength="120" required
                          :value="old('description', $expense->description ?? '')" placeholder="e.g. Groceries at Lotus's" />
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="amount" value="Amount (RM)" />
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-zinc-500">RM</span>
                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" inputmode="decimal" required
                              class="block w-full pl-11" x-model="amount" placeholder="0.00" />
            </div>
            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="expense_date" value="Date" />
            <x-text-input id="expense_date" name="expense_date" type="date" required class="mt-1 block w-full"
                          :value="old('expense_date', $expense?->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d'))"
                          max="{{ now()->format('Y-m-d') }}" />
            <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="paid_by" value="Paid by" />
            <select id="paid_by" name="paid_by" required
                    class="mt-1 block w-full rounded-lg border-zinc-700 bg-zinc-950 text-white focus:border-emerald-500 focus:ring-emerald-500">
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((int) old('paid_by', $expense->paid_by ?? Auth::id()) === $member->id)>
                        {{ $member->displayName() }}{{ $member->id === Auth::id() ? ' (you)' : '' }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('paid_by')" class="mt-2" />
        </div>
    </div>

    {{-- Split method --}}
    <div>
        <x-input-label value="How should it be split?" />
        <input type="hidden" name="split_type" :value="splitType">
        <div class="mt-2 grid grid-cols-2 gap-2 rounded-xl bg-zinc-950 p-1">
            <button type="button" @click="splitType = 'equal'"
                    :class="splitType === 'equal' ? 'bg-zinc-800 text-white shadow' : 'text-zinc-400 hover:text-white'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition">Split equally</button>
            <button type="button" @click="splitType = 'custom'"
                    :class="splitType === 'custom' ? 'bg-zinc-800 text-white shadow' : 'text-zinc-400 hover:text-white'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition">Custom amounts</button>
        </div>
    </div>

    <div class="divide-y divide-zinc-800 rounded-xl border border-zinc-800">
        <template x-for="m in members" :key="m.id">
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                {{-- Equal: tick who shares the cost --}}
                <label x-show="splitType === 'equal'" class="flex flex-1 cursor-pointer items-center gap-3">
                    <input type="checkbox" name="participants[]" :value="m.id" x-model.number="participants"
                           :disabled="splitType !== 'equal'"
                           class="rounded border-zinc-600 bg-zinc-950 text-emerald-500 focus:ring-emerald-500">
                    <span class="text-sm text-zinc-200" x-text="m.name"></span>
                </label>
                <span x-show="splitType === 'equal'" class="text-sm font-medium"
                      :class="participants.includes(m.id) ? 'text-emerald-300' : 'text-zinc-600'"
                      x-text="participants.includes(m.id) ? rm(equalShare(m.id)) : '—'"></span>

                {{-- Custom: type each person's amount --}}
                <span x-show="splitType === 'custom'" class="flex-1 text-sm text-zinc-200" x-text="m.name"></span>
                <div x-show="splitType === 'custom'" class="relative w-36">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-xs text-zinc-500">RM</span>
                    <input type="number" step="0.01" min="0" :name="'custom[' + m.id + ']'" x-model="custom[m.id]"
                           :disabled="splitType !== 'custom'" placeholder="0.00"
                           class="block w-full rounded-lg border-zinc-700 bg-zinc-950 py-1.5 pl-10 text-right text-sm text-white focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>
        </template>
    </div>

    {{-- Live summary --}}
    <div class="rounded-xl px-4 py-3 text-sm"
         :class="ok ? 'bg-emerald-500/10 text-emerald-200' : 'bg-amber-500/10 text-amber-200'">
        <span x-text="summary"></span>
    </div>
    <x-input-error :messages="array_merge($errors->get('participants'), $errors->get('custom'))" />

    <div class="flex justify-end gap-3">
        {{ $extraButtons ?? '' }}
        <x-primary-button x-bind:disabled="!ok">{{ $submitLabel }}</x-primary-button>
    </div>
</form>

@once
    @push('scripts')
        <script>
            function expenseForm(init) {
                return {
                    ...init,
                    get totalSen() {
                        const v = parseFloat(this.amount);
                        return isNaN(v) ? 0 : Math.round(v * 100);
                    },
                    // Same rule as the server: leftover sen go to the first people ticked.
                    equalShare(id) {
                        const people = this.members.map(m => m.id).filter(x => this.participants.includes(x));
                        const n = people.length;
                        if (!n) return 0;
                        const base = Math.floor(this.totalSen / n);
                        const rem = this.totalSen - base * n;
                        return base + (people.indexOf(id) < rem ? 1 : 0);
                    },
                    get customSen() {
                        return this.members.reduce((s, m) => s + Math.round((parseFloat(this.custom[m.id]) || 0) * 100), 0);
                    },
                    get ok() {
                        if (this.totalSen <= 0) return false;
                        return this.splitType === 'equal'
                            ? this.participants.length > 0
                            : this.customSen === this.totalSen;
                    },
                    get summary() {
                        if (this.totalSen <= 0) return 'Enter an amount to see the split.';
                        if (this.splitType === 'equal') {
                            const n = this.participants.length;
                            if (!n) return 'Tick at least one person to share this expense.';
                            const each = this.totalSen / n / 100;
                            return `${this.rm(this.totalSen)} split between ${n} ${n === 1 ? 'person' : 'people'} ≈ RM${each.toFixed(2)} each.`;
                        }
                        const diff = this.totalSen - this.customSen;
                        if (diff === 0) return `Custom amounts add up to ${this.rm(this.totalSen)}. Ready to save.`;
                        return diff > 0
                            ? `${this.rm(diff)} left to assign (of ${this.rm(this.totalSen)}).`
                            : `${this.rm(-diff)} over the total of ${this.rm(this.totalSen)}.`;
                    },
                    rm(sen) {
                        return 'RM' + (sen / 100).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },
                };
            }
        </script>
    @endpush
@endonce
