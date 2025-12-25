@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-display text-santa-red mb-2">{{ __('reveal.title') }}</h1>
        <p class="text-gray-600">{{ __('reveal.description') }}</p>
    </div>

    <div class="bg-white shadow-xl rounded-2xl overflow-hidden p-8">

        <div class="mb-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 text-santa-red mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">{{ __('reveal.greeting', ['name' => $participant->name]) }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ __('reveal.private_message') }}</p>
        </div>

        <!-- Telegram Login -->
        <div>
            <a href="https://t.me/{{ $botUsername }}?start=auth_{{ $authToken }}"
               target="_blank"
               class="w-full flex items-center justify-center gap-2 bg-[#0088cc] hover:bg-[#006699] text-white py-3 rounded-lg font-semibold text-lg shadow-lg transform transition hover:-translate-y-0.5">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
                {!! __('reveal.telegram_login_btn') !!}
            </a>
            <p class="text-xs text-gray-400 text-center mt-3">
                {!! __('reveal.telegram_instructions') !!}
            </p>
        </div>
    </div>
</div>
@endsection
