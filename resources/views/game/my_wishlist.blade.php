@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-display text-santa-dark">{{ __('wishlist.my_wishlist_title') }}</h1>
        <a href="{{ route('game.myGames') }}" class="text-gray-500 hover:text-gray-700 font-semibold text-sm">
            &larr; {{ __('game.back_to_games') }}
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('game.updateMyWishlist') }}" method="POST">
        @csrf

        <!-- Global Shipping Address -->
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('wishlist.global_address') }}</h2>
            <p class="text-sm text-gray-600 mb-4">{{ __('wishlist.global_address_help') }}</p>
            <textarea
                name="shipping_address"
                rows="3"
                required
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-santa-green focus:border-santa-green outline-none transition-all"
                placeholder="{{ __('reveal_result.address_placeholder') }}"
            >{{ old('shipping_address', auth()->user()->shipping_address) }}</textarea>
            @if(!auth()->user()->shipping_address)
                <p class="text-xs text-red-500 mt-2">
                    {{ __('wishlist.address_required_warning') }}
                </p>
            @endif
        </div>

        <!-- Wishlists for Each Game -->
        @if($participations->count() > 0)
            <div class="space-y-6">
                <h2 class="text-xl font-bold text-gray-800">{{ __('wishlist.game_wishlists') }}</h2>

                @foreach($participations as $participation)
                    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-800">{{ $participation->game->title ?? 'Secret Santa' }}</h3>
                            @if($participation->game->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $participation->game->description }}</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                {{ __('wishlist.your_wishlist_for_game') }}
                            </label>
                            <textarea
                                name="wishlists[{{ $participation->id }}]"
                                rows="4"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-santa-red focus:border-santa-red outline-none transition-all"
                                placeholder="{{ __('reveal_result.wishlist_placeholder') }}"
                            >{{ old('wishlists.' . $participation->id, $participation->wishlist_text) }}</textarea>

                            @if($participation->wishlist_text)
                                <p class="text-xs text-gray-500 mt-2">
                                    ✅ {{ __('wishlist.santa_will_see') }}
                                </p>
                            @else
                                <p class="text-xs text-gray-400 mt-2">
                                    💡 {{ __('wishlist.add_wishlist_hint') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-santa-mist rounded-3xl p-8 text-center">
                <div class="text-6xl mb-4">🎁</div>
                <p class="text-gray-600 mb-4">{{ __('wishlist.no_games_yet') }}</p>
                <a href="{{ route('game.create') }}" class="inline-block btn-primary px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                    {{ __('welcome.start_new_game_btn') }}
                </a>
            </div>
        @endif

        <!-- Save Button -->
        <div class="mt-8 flex justify-end">
            <button type="submit" class="btn-primary px-12 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all">
                {{ __('wishlist.save_all') }} ✨
            </button>
        </div>
    </form>
</div>
@endsection
