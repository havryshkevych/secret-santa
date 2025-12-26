@extends('layouts.app')

@section('meta')
    @php
        // Use custom OG image if exists, otherwise use placeholder
        $ogImage = file_exists(public_path('images/og-image.png'))
            ? asset('images/og-image.png')
            : 'https://placehold.co/1200x630/c41e3a/white?text=' . urlencode('🎅 ' . ($game->title ?? 'Secret Santa'));

        // Build description with game info
        $ogDescription = ($game->description ? $game->description . ' • ' : '') .
                        __('game.login_to_join') .
                        ($game->participants->count() > 0 ? ' • ' . $game->participants->count() . ' ' . __('game.joined') : '');
    @endphp

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('game.join', $game->join_token) }}">
    <meta property="og:site_name" content="Secret Santa">
    <meta property="og:title" content="🎅 {{ $game->title ?? 'Secret Santa' }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ route('game.join', $game->join_token) }}">
    <meta property="twitter:title" content="🎅 {{ $game->title ?? 'Secret Santa' }}">
    <meta property="twitter:description" content="{{ $ogDescription }}">
    <meta property="twitter:image" content="{{ $ogImage }}">
@endsection

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">🎅</div>
            <h1 class="text-3xl font-display text-santa-dark mb-2">{{ $game->title ?? 'Secret Santa' }}</h1>
            @if($game->description)
                <p class="text-gray-600 mt-2 whitespace-pre-line">{{ $game->description }}</p>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-santa-mist rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">👥 {{ __('game.participants') }}</h3>
                <span class="text-sm text-gray-600">{{ $game->participants->count() }} {{ __('game.joined') }}</span>
            </div>

            @if($game->participants->count() > 0)
                <div class="space-y-2">
                    @foreach($game->participants as $participant)
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <span>✓</span>
                            <span>{{ $participant->name }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 italic">{{ __('game.no_participants_yet') }}</p>
            @endif
        </div>

        @if($game->is_started)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-center">
                {{ __('game.already_started') }}
            </div>
        @elseif($alreadyJoined)
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-center mb-4">
                ✅ {{ __('game.you_already_joined') }}
            </div>

            <!-- Bot reminder section -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl p-6 mb-6 border-2 border-blue-300 shadow-lg">
                <h3 class="text-lg font-bold text-blue-900 mb-2">
                    {{ __('game.start_bot_title') }}
                </h3>
                <p class="text-blue-800 mb-4">
                    {{ __('game.start_bot_description') }}
                </p>
                <a href="https://t.me/{{ env('TELEGRAM_BOT_USERNAME', 'little_santa_bot') }}?start=join_{{ $game->join_token }}"
                   target="_blank"
                   class="inline-flex items-center justify-center w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                    </svg>
                    {{ __('game.open_bot_button') }}
                </a>
            </div>

            <div class="bg-gradient-to-br from-santa-gold/10 to-santa-red/10 rounded-2xl p-6 mb-6 border-2 border-santa-gold shadow-lg">
                <form action="{{ route('game.updateMyWishlist') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-lg font-bold text-santa-dark mb-3">
                             🎁 {{ __('wishlist.your_wishlist_for_game') }}
                        </label>
                        <textarea
                            name="wishlists[{{ $participant->id }}]"
                            rows="4"
                            class="w-full px-4 py-3 rounded-xl border-2 border-santa-gold/30 bg-white focus:ring-2 focus:ring-santa-gold focus:border-santa-gold outline-none transition-all"
                            placeholder="{{ __('reveal_result.wishlist_placeholder') }}"
                        >{{ old('wishlists.' . $participant->id, $participant->wishlist_text) }}</textarea>
                        @if($participant->wishlist_text)
                            <p class="text-sm text-green-700 mt-2 font-semibold">
                                ✅ {{ __('wishlist.santa_will_see') }}
                            </p>
                        @else
                            <p class="text-sm text-gray-600 mt-2">
                                💡 {{ __('wishlist.add_wishlist_hint') }}
                            </p>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="block text-lg font-bold text-santa-dark mb-3">
                             📦 {{ __('reveal_result.your_address_label') }}
                        </label>
                        <textarea
                            name="shipping_address"
                            rows="3"
                            class="w-full px-4 py-3 rounded-xl border-2 border-santa-gold/30 bg-white focus:ring-2 focus:ring-santa-gold focus:border-santa-gold outline-none transition-all"
                            placeholder="{{ __('reveal_result.address_placeholder') }}"
                        >{{ old('shipping_address', $participant->shipping_address) }}</textarea>
                        @if($participant->shipping_address)
                            <p class="text-sm text-green-700 mt-2 font-semibold">
                                ✅ {{ __('wishlist.santa_will_see') }}
                            </p>
                        @else
                            <p class="text-sm text-gray-600 mt-2">
                                📍 {{ __('wishlist.add_address_hint') }}
                            </p>
                        @endif
                    </div>

                    <button type="submit" class="w-full bg-santa-gold hover:bg-yellow-500 text-white px-6 py-3 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                        💾 {{ __('result.save_btn') }}
                    </button>
                </form>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('game.myGames') }}" class="w-full text-center btn-primary px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                    {{ __('game.my_games_title') }}
                </a>
                <form action="{{ route('game.leave', $game->id) }}" method="POST" onsubmit="return confirm('{{ __('game.confirm_leave') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-xl font-semibold shadow-lg transition-colors">
                        {{ __('game.leave_game') }}
                    </button>
                </form>
            </div>
        @elseif(auth()->check())
            <form action="{{ route('game.join.post', $game->join_token) }}" method="POST">
                @csrf
                <button type="submit" class="w-full btn-primary py-4 rounded-2xl font-bold text-lg shadow-lg hover:shadow-xl transition-all">
                    🎁 {{ __('game.join_game') }}
                </button>
            </form>
        @else
            <div class="text-center">
                <!-- Primary option: Join via Bot -->
                <div class="mb-6">
                    <p class="text-gray-700 mb-4 font-semibold">{{ __('game.login_to_join') }}</p>
                    <a href="https://t.me/{{ env('TELEGRAM_BOT_USERNAME', 'little_santa_bot') }}?start=join_{{ $game->join_token }}"
                       class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-2xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                        </svg>
                        {{ __('game.join_via_bot') }}
                    </a>
                    <p class="text-sm text-gray-500 mt-3">{{ __('game.join_via_bot_hint') }}</p>
                </div>

                <!-- Alternative option: Web login -->
                <details class="mt-4">
                    <summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700">
                        {{ __('game.alternative_web_login') }}
                    </summary>
                    <div class="mt-4 flex justify-center">
                        <script async src="https://telegram.org/js/telegram-widget.js?22"
                            data-telegram-login="{{ env('TELEGRAM_BOT_USERNAME', 'little_santa_bot') }}"
                            data-size="large"
                            data-onauth="onTelegramAuth(user)"
                            data-request-access="write"></script>
                    </div>
                </details>
            </div>
        @endif

        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-500">
                {{ __('game.share_link_instruction') }}
            </p>
            <div class="mt-3 flex gap-2">
                <input type="text" readonly value="{{ route('game.join', $game->join_token) }}"
                       class="flex-1 px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg"
                       id="joinLink">
                <button onclick="copyLink()" class="px-4 py-2 bg-santa-gold text-white rounded-lg text-sm font-semibold hover:opacity-90">
                    {{ __('game.copy_link') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function copyLink() {
    const input = document.getElementById('joinLink');
    input.select();
    document.execCommand('copy');
    alert('{{ __('game.link_copied') }}');
}

function onTelegramAuth(user) {
    // Send auth data to server
    const params = new URLSearchParams({
        id: user.id,
        first_name: user.first_name,
        last_name: user.last_name || '',
        username: user.username || '',
        photo_url: user.photo_url || '',
        auth_date: user.auth_date,
        hash: user.hash,
        redirect: '{{ route('game.join', $game->join_token) }}'
    });

    // Redirect to login endpoint with auth data
    window.location.href = '{{ route('login.telegram') }}?' + params.toString();
}
</script>
@endsection
