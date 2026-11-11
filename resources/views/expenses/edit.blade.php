<x-app-layout>
    <x-slot name="title">Edit expense</x-slot>

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('groups.show', $group) }}" class="text-sm text-zinc-400 hover:text-white">← Back to {{ $group->name }}</a>

        <h1 class="mb-6 mt-3 text-3xl font-bold tracking-tight text-white">Edit expense</h1>

        <x-card>
            @include('expenses._form', [
                'expense' => $expense,
                'action' => route('expenses.update', $expense),
                'method' => 'PUT',
                'submitLabel' => 'Save changes',
                'extraButtons' => new \Illuminate\Support\HtmlString(
                    '<a href="'.route('groups.show', $group).'" class="inline-flex items-center rounded-lg border border-zinc-700 px-4 py-2.5 text-sm font-semibold text-zinc-200 hover:bg-zinc-800">Cancel</a>'
                ),
            ])
        </x-card>
    </div>
</x-app-layout>
