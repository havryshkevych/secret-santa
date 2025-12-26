@extends('layouts.app')

@section('content')
<div class="text-center py-4">
    <div class="mb-8 flex justify-center">
        <!-- Minimal SVG Icon -->
        <svg class="w-24 h-24 text-santa-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
        </svg>
    </div>

    <h1 class="text-4xl font-display text-santa-dark mb-4">{{ __('welcome.hero_title') }}</h1>
    <p class="text-gray-600 mb-8 max-w-lg mx-auto">
        {{ __('welcome.hero_text') }}
    </p>

    @auth
        <div class="mb-8 flex flex-wrap justify-center gap-4">
            <a href="{{ route('game.myGames') }}" class="btn-primary inline-flex items-center px-8 py-3 rounded-full text-lg font-semibold shadow-lg">
                <span class="mr-2">🎮</span> {{ __('welcome.my_games_btn') }}
            </a>
            <a href="{{ route('game.myWishlist') }}" class="bg-santa-green text-white inline-flex items-center px-8 py-3 rounded-full text-lg font-semibold shadow-lg hover:opacity-90 transition-opacity">
                <span class="mr-2">⚙️</span> {{ __('wishlist.my_wishlist_title') }}
            </a>
            <a href="{{ route('game.create') }}" class="bg-santa-gold text-white inline-flex items-center px-8 py-3 rounded-full text-lg font-semibold shadow-lg hover:opacity-90 transition-opacity">
                <span class="mr-2">🎁</span> {{ __('welcome.start_new_game_btn') }}
            </a>
        </div>
    @else
        <div class="mb-12 flex flex-col items-center tg-login-section">
            <p class="text-gray-500 text-sm mb-4">{{ __('welcome.auth_prompt') }}</p>
            <script async src="https://telegram.org/js/telegram-widget.js?22"
                data-telegram-login="{{ env('TELEGRAM_BOT_USERNAME', 'little_santa_bot') }}"
                data-size="large"
                data-auth-url="{{ route('login.telegram') }}"
                data-request-access="write"></script>
        </div>
    @endauth

    <!-- How It Works Section -->
    <div class="mt-8 border-t border-gray-200 pt-10">
        <h3 class="text-2xl font-display text-santa-green mb-8">{{ __('welcome.how_it_works_title') }}</h3>
        
        <div class="grid md:grid-cols-3 gap-6 text-left">
            <!-- Step 1 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">📝</div>
                <h4 class="font-bold text-gray-800 mb-2">{{ __('welcome.step1_title') }}</h4>
                <p class="text-gray-600 text-sm">
                    {{ __('welcome.step1_text') }}
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🚫</div>
                <h4 class="font-bold text-gray-800 mb-2">{{ __('welcome.step2_title') }}</h4>
                <p class="text-gray-600 text-sm">
                    {{ __('welcome.step2_text') }}
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🎯</div>
                <h4 class="font-bold text-gray-800 mb-2">{{ __('welcome.step3_title') }}</h4>
                <p class="text-gray-600 text-sm">
                    {{ __('welcome.step3_text') }}
                </p>
            </div>

            <!-- Step 4 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🔗</div>
                <h4 class="font-bold text-gray-800 mb-2">{{ __('welcome.step4_title') }}</h4>
                <p class="text-gray-600 text-sm">
                    {{ __('welcome.step4_text') }}
                </p>
            </div>

            <!-- Step 5 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">📱</div>
                <h4 class="font-bold text-gray-800 mb-2">{{ __('welcome.step5_title') }}</h4>
                <p class="text-gray-600 text-sm">
                    {!! __('welcome.step5_text') !!}
                </p>
            </div>

            <!-- Step 6 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">✨</div>
                <h4 class="font-bold text-gray-800 mb-2">{{ __('welcome.step6_title') }}</h4>
                <p class="text-gray-600 text-sm">
                    {{ __('welcome.step6_text') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="mt-12 border-t border-gray-200 pt-10">
        <h3 class="text-2xl font-display text-santa-green mb-8">{{ __('welcome.features_title') }}</h3>
        
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">🆓</div>
                <p class="text-gray-700 font-semibold text-sm">{{ __('welcome.feature_free') }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">📵</div>
                <p class="text-gray-700 font-semibold text-sm">{{ __('welcome.feature_no_reg') }}</p>
            </div>
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">🤖</div>
                <p class="text-gray-700 font-semibold text-sm">{{ __('welcome.feature_bot') }}</p>
            </div>
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">🎲</div>
                <p class="text-gray-700 font-semibold text-sm">{{ __('welcome.feature_fair') }}</p>
            </div>
        </div>
    </div>

    <!-- CTA -->
    @auth
        <div class="mt-12 pt-8">
            <a href="{{ route('game.create') }}" class="btn-primary inline-flex items-center px-10 py-4 rounded-full text-xl font-bold shadow-xl transform hover:scale-105 transition-transform">
                <span class="mr-3">🎅</span> {{ __('welcome.cta_button') }}
            </a>
            <p class="text-gray-500 text-sm mt-4">{{ __('welcome.cta_text') }}</p>
        </div>
    @endauth
</div>
@endsection
