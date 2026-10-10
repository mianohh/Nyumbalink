<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-cover bg-center p-6 md:p-10" style="background-image: linear-gradient(rgba(17, 24, 39, 0.72), rgba(17, 24, 39, 0.72)), url('{{ asset('images/login-bg.jpg') }}')">
            <div class="flex w-full max-w-sm flex-col gap-2 rounded-2xl bg-white p-8 shadow-2xl dark:bg-neutral-900">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 pb-2 font-medium" wire:navigate>
                    <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name') }}" class="h-16 w-16 rounded-xl object-cover shadow-md" />
                    <span class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ config('app.name') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
