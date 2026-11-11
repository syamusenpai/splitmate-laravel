<x-app-layout>
    <x-slot name="title">Account</x-slot>

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex items-center gap-4">
            <x-avatar :user="Auth::user()" size="h-14 w-14 text-lg" />
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-white">Account</h1>
                <p class="text-sm text-zinc-400">Member since {{ Auth::user()->created_at->format('F Y') }}</p>
            </div>
        </div>

        <x-card>@include('profile.partials.update-profile-information-form')</x-card>
        <x-card>@include('profile.partials.update-password-form')</x-card>
        <x-card class="border-red-500/20">@include('profile.partials.delete-user-form')</x-card>
    </div>
</x-app-layout>
