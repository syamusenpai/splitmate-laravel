@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
         class="mb-6 flex items-start justify-between gap-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        <span>{{ session('status') }}</span>
        <button type="button" @click="show = false" class="text-emerald-300 hover:text-white" aria-label="Dismiss">&times;</button>
    </div>
@endif
@if ($errors->has('member'))
    <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ $errors->first('member') }}</div>
@endif
