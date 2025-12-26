@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto text-center" x-data="{ revealed: false }" x-init="setTimeout(() => revealed = true, 500)">
    
    <div class="mb-12">
        <h1 class="text-4xl font-display text-santa-red mb-2">{{ __('reveal_result.title') }}</h1>
        
        @if($participant->game->description)
            <div class="max-w-sm mx-auto bg-santa-gold/10 border border-santa-gold/20 rounded-xl p-4 text-sm text-santa-dark/80 italic">
                <span class="block font-bold text-xs uppercase tracking-widest text-santa-gold mb-1">{{ __('reveal_result.game_desc_label') }}</span>
                {!! nl2br(e($participant->game->description)) !!}
            </div>
        @endif
    </div>

    <div class="relative">
        <!-- Gift Box Animation Container -->
        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden p-6 sm:p-12 border-4 border-santa-green/20 relative z-10 transform transition-all duration-1000"
             :class="{ 'scale-105 rotate-1': revealed, 'scale-100': !revealed }">
            
            <div class="mb-6">
                <span class="text-6xl">🎁</span>
            </div>
            
            <div class="transition-opacity duration-1000 delay-500"
                 :class="{ 'opacity-100': revealed, 'opacity-0': !revealed }">
                 <p class="text-sm uppercase tracking-widest text-gray-400 font-semibold mb-2">{{ __('reveal_result.you_gift_to') }}</p>
                <h2 class="text-4xl font-bold text-gray-900 break-words font-display text-santa-green mb-4">
                    {{ $recipient->name }}
                </h2>

                @if($recipient->wishlist_text)
                    <div class="mt-6 bg-green-50 rounded-xl p-4 text-left border border-green-100 italic text-gray-700">
                        <p class="text-xs font-bold uppercase tracking-wider text-santa-green mb-2 opacity-70">{{ __('reveal_result.wishlist_label') }}</p>
                        {!! nl2br(e($recipient->wishlist_text)) !!}
                    </div>
                @else
                    <p class="text-sm text-gray-400 mt-2 italic">{{ __('reveal_result.no_wishlist') }}</p>
                @endif

                @if($recipient->shipping_address)
                    <div class="mt-4 bg-blue-50 rounded-xl p-4 text-left border border-blue-100 text-gray-700">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-2 opacity-70">{{ __('reveal_result.address_label') }}</p>
                        {!! nl2br(e($recipient->shipping_address)) !!}
                    </div>
                @else
                    <p class="text-sm text-gray-400 mt-2 italic">{{ __('reveal_result.no_address') }}</p>
                @endif
            </div>
            
        </div>

        <!-- Current User's Wishlist Editor -->
        <div class="mt-8 bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-100 p-6 text-left" x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <span class="mr-2">📝</span> {{ __('reveal_result.your_wishlist') }}
                </h3>
                <button @click="editing = !editing"
                    class="px-2 py-1 text-xs sm:px-3 sm:py-1.5 sm:text-sm font-semibold rounded-lg transition-colors cursor-pointer"
                    :class="editing ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-santa-green text-white hover:bg-santa-dark'">
                    <span x-show="!editing">{{ __('reveal_result.edit_btn') }}</span>
                    <span x-show="editing">{{ __('reveal_result.cancel_btn') }}</span>
                </button>
            </div>

            @if(session('status'))
                <div class="mb-4 p-2 bg-green-50 text-green-600 text-xs rounded border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div x-show="!editing" class="space-y-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">{{ __('reveal_result.your_wishlist_label') }}</p>
                    @if($participant->wishlist_text)
                        <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ $participant->wishlist_text }}</p>
                    @else
                        <p class="text-gray-400 text-sm italic">{{ __('reveal_result.no_your_wishlist') }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">{{ __('reveal_result.your_address_label') }}</p>
                    @if($participant->shipping_address)
                        <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ $participant->shipping_address }}</p>
                    @else
                        <p class="text-gray-400 text-sm italic">{{ __('reveal_result.no_your_address') }}</p>
                    @endif
                </div>
            </div>

            <form x-show="editing" action="{{ route('wishlist.update', ['gameId' => $gameId, 'participantId' => $participant->id, 'token' => $token]) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-1">{{ __('reveal_result.your_wishlist_label') }}</label>
                        <textarea name="wishlist" rows="3" 
                            class="w-full px-3 py-2 text-sm text-gray-700 border rounded-lg focus:outline-none focus:ring-2 focus:ring-santa-green"
                            placeholder="{{ __('reveal_result.wishlist_placeholder') }}">{{ $participant->wishlist_text }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-1">{{ __('reveal_result.your_address_label') }}</label>
                        <textarea name="shipping_address" rows="3" 
                            class="w-full px-3 py-2 text-sm text-gray-700 border rounded-lg focus:outline-none focus:ring-2 focus:ring-santa-green"
                            placeholder="{{ __('reveal_result.address_placeholder') }}">{{ $participant->shipping_address }}</textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn-primary px-4 py-1.5 rounded-lg text-sm font-semibold">
                        {{ __('reveal_result.save_all') }}
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Decoration -->
        <div class="absolute top-0 left-0 -mt-4 -ml-4 w-24 h-24 bg-red-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 -mb-4 -mr-4 w-24 h-24 bg-green-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000 pointer-events-none"></div>
    </div>

    <div class="mt-12 space-y-6">
        @if($participant->telegram_chat_id || (Auth::check() && Auth::user()->telegram_id))
            <form action="{{ route('reveal.resend', ['gameId' => $gameId, 'participantId' => $participant->id, 'token' => $token]) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0088cc] hover:bg-[#006699] text-white rounded-full font-semibold shadow-md transition-all">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    {{ __('reveal_result.resend_telegram') }}
                </button>
            </form>
        @endif
        <p class="text-gray-500 italic">{{ __('reveal_result.secret_reminder') }}</p>
        
        <a href="{{ route('home') }}" class="inline-block text-santa-green hover:text-santa-dark font-medium underline">
            {{ __('reveal_result.back_home') }}
        </a>
    </div>

</div>

<style>
    .font-display { font-family: 'Mountains of Christmas', cursive, serif; } /* Fallback or ensure font is loaded */
</style>
@endsection
