@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto text-center" x-data="{ revealed: false }" x-init="setTimeout(() => revealed = true, 500)">
    
    <div class="mb-12">
        <h1 class="text-4xl font-display text-santa-red mb-2">Таємницю розкрито!</h1>
        <p class="text-gray-600 mb-4">Ти даруватимеш подарунок для...</p>
        
        @if($participant->game->description)
            <div class="max-w-sm mx-auto bg-santa-gold/10 border border-santa-gold/20 rounded-xl p-4 text-sm text-santa-dark/80 italic">
                <span class="block font-bold text-xs uppercase tracking-widest text-santa-gold mb-1">Опис гри:</span>
                {!! nl2br(e($participant->game->description)) !!}
            </div>
        @endif
    </div>

    <div class="relative">
        <!-- Gift Box Animation Container -->
        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden p-12 border-4 border-santa-green/20 relative z-10 transform transition-all duration-1000"
             :class="{ 'scale-105 rotate-1': revealed, 'scale-100': !revealed }">
            
            <div class="mb-6">
                <span class="text-6xl">🎁</span>
            </div>
            
            <div class="transition-opacity duration-1000 delay-500"
                 :class="{ 'opacity-100': revealed, 'opacity-0': !revealed }">
                <p class="text-sm uppercase tracking-widest text-gray-400 font-semibold mb-2">ТИ ДАРУЄШ ПОДАРУНОК ДЛЯ</p>
                <h2 class="text-4xl font-bold text-gray-900 break-words font-display text-santa-green mb-4">
                    {{ $recipient->name }}
                </h2>

                @if($recipient->wishlist_text)
                    <div class="mt-6 bg-green-50 rounded-xl p-4 text-left border border-green-100 italic text-gray-700">
                        <p class="text-xs font-bold uppercase tracking-wider text-santa-green mb-2 opacity-70">Побажання до подарунка:</p>
                        {!! nl2br(e($recipient->wishlist_text)) !!}
                    </div>
                @else
                    <p class="text-sm text-gray-400 mt-2 italic">Отримувач ще не додав побажань.</p>
                @endif
            </div>
            
        </div>

        <!-- Current User's Wishlist Editor -->
        <div class="mt-8 bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-100 p-6 text-left" x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <span class="mr-2">📝</span> Твої побажання
                </h3>
                <button @click="editing = !editing" 
                    class="px-3 py-1.5 text-sm font-semibold rounded-lg transition-colors cursor-pointer"
                    :class="editing ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-santa-green text-white hover:bg-santa-dark'"
                    x-text="editing ? '✕ Скасувати' : '✏️ Редагувати'">
                </button>
            </div>

            @if(session('status'))
                <div class="mb-4 p-2 bg-green-50 text-green-600 text-xs rounded border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div x-show="!editing">
                @if($participant->wishlist_text)
                    <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ $participant->wishlist_text }}</p>
                @else
                    <p class="text-gray-400 text-sm italic">Ти ще не додав побажань до свого списку.</p>
                @endif
            </div>

            <form x-show="editing" action="{{ route('wishlist.update', ['gameId' => $gameId, 'participantId' => $participant->id, 'token' => $token]) }}" method="POST">
                @csrf
                <textarea name="wishlist" rows="4" 
                    class="w-full px-3 py-2 text-sm text-gray-700 border rounded-lg focus:outline-none focus:ring-2 focus:ring-santa-green"
                    placeholder="Напр. Люблю шоколад, книжки фантастики або теплі шкарпетки!">{{ $participant->wishlist_text }}</textarea>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="btn-primary px-4 py-1.5 rounded-lg text-sm font-semibold">
                        Зберегти
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Decoration -->
        <div class="absolute top-0 left-0 -mt-4 -ml-4 w-24 h-24 bg-red-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 -mb-4 -mr-4 w-24 h-24 bg-green-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000 pointer-events-none"></div>
    </div>

    <div class="mt-12 space-y-4">
        <p class="text-gray-500 italic">«Пам'ятай, це таємниця! Нікому не кажи.» 🤫</p>
        
        <a href="{{ route('home') }}" class="inline-block text-santa-green hover:text-santa-dark font-medium underline">
            На головну
        </a>
    </div>

</div>

<style>
    .font-display { font-family: 'Mountains of Christmas', cursive, serif; } /* Fallback or ensure font is loaded */
</style>
@endsection
