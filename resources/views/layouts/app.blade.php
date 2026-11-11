<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'SplitMate') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 font-sans text-zinc-100 antialiased">
        <div class="pointer-events-none fixed right-1/4 top-0 h-[450px] w-[450px] rounded-full bg-emerald-500/5 blur-[100px]"></div>

        <div class="relative min-h-screen">
            @include('layouts.navigation')

            <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
