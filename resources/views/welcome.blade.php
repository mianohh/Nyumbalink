<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-zinc-50 dark:bg-zinc-900">
        <div class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-8 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name') }}" class="mx-auto mb-4 h-16 w-16 rounded-xl object-cover" />

            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ config('app.name') }}</h1>

            <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                Rental property management for houses, tenants, agreements and payments.
            </p>

            <div class="mt-6">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-block rounded-lg bg-[#1e3a5f] px-6 py-2.5 font-medium text-white transition hover:opacity-90">
                        {{ __('Go to Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-block rounded-lg bg-[#1e3a5f] px-6 py-2.5 font-medium text-white transition hover:opacity-90">
                        {{ __('Log in') }}
                    </a>
                @endauth
            </div>
        </div>
    </body>
</html>
