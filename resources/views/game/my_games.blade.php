@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-8">
        <h1 class="text-3xl font-display text-santa-dark text-center sm:text-left">{{ __('game.my_games_title') }}</h1>
        <div class="flex gap-3 w-full sm:w-auto">
            <a href="{{ route('game.myWishlist') }}" class="flex-1 sm:flex-initial bg-santa-green text-white px-6 py-2 rounded-full font-semibold shadow text-sm text-center hover:opacity-90 transition-opacity">
                ⚙️ {{ __('wishlist.my_wishlist_title') }}
            </a>
            <a href="{{ route('game.create') }}" class="flex-1 sm:flex-initial btn-primary px-6 py-2 rounded-full font-semibold shadow text-sm text-center">
                🎁 {{ __('game.btn_new_game') }}
            </a>
        </div>
    </div>

    @if($organizedGames->isEmpty() && $participations->isEmpty())
        <div class="text-center py-12 bg-white/50 rounded-3xl border border-dashed border-gray-300">
            <div class="text-6xl mb-4">🦌</div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">{{ __('game.my_games_empty_title') }}</h3>
            <p class="text-gray-500 mb-8">{{ __('game.my_games_empty_text') }}</p>
            <a href="{{ route('game.create') }}" class="text-santa-red font-bold hover:underline">{{ __('game.create_first_game') }} &rarr;</a>
        </div>
    @endif

    @if($organizedGames->isNotEmpty())
        <div class="mb-12">
            <h3 class="text-xl font-display text-santa-green mb-6 flex items-center">
                <span class="mr-2">👨‍💻</span> {{ __('game.organized_games') }}
            </h3>
            <div class="grid gap-4">
                @foreach($organizedGames as $game)
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:shadow-md transition-shadow">
                        <div class="text-center sm:text-left">
                            <h4 class="font-bold text-gray-800 text-lg">{{ $game->title ?? 'Secret Santa' }}</h4>
                            <p class="text-gray-500 text-sm">{{ __('game.participants_count', ['count' => $game->participants_count]) }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="{{ route('game.result', $game->id) }}" class="px-4 py-2 bg-santa-mist text-santa-dark rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors text-center">
                                {{ __('game.btn_results') }}
                            </a>
                            <a href="{{ route('game.edit', $game->id) }}" class="px-4 py-2 bg-santa-gold text-white rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity text-center">
                                {{ __('game.btn_settings') }}
                            </a>
                            <form action="{{ route('game.destroy', $game->id) }}" method="POST" onsubmit="return confirm('{{ __('game.confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-semibold hover:bg-red-600 transition-colors text-center w-full">
                                    {{ __('game.btn_delete') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($participations->isNotEmpty())
        <div>
            <h3 class="text-xl font-display text-santa-green mb-6 flex items-center">
                <span class="mr-2">🎁</span> {{ __('game.participating_games') }}
            </h3>
            <div class="grid gap-4">
                @foreach($participations as $p)
                    @php 
                        $game = $p->game;
                        $assignment = $p->assignmentAsSanta;
                    @endphp
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:shadow-md transition-shadow">
                        <div class="text-center sm:text-left">
                            <h4 class="font-bold text-gray-800 text-lg">{{ $game->title ?? 'Secret Santa' }}</h4>
                            @if($assignment)
                                <p class="text-santa-red text-sm font-semibold mt-1">
                                    {{ __('game.you_gift_to', ['name' => $assignment->recipient->name]) }}
                                </p>
                            @else
                                <p class="text-gray-400 text-sm mt-1 italic">{{ __('game.pairs_not_assigned') }}</p>
                            @endif
                        </div>
                        @if($assignment && $p->reveal_token)
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="{{ route('reveal.show', ['gameId' => $game->id, 'participantId' => $p->id, 'token' => $p->reveal_token]) }}" class="px-4 py-2 bg-santa-red text-white rounded-lg text-sm font-semibold hover:bg-santa-dark transition-colors text-center whitespace-nowrap">
                                    {{ __('game.btn_view') }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-12 pt-8 border-t border-gray-200 flex justify-center">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-gray-400 hover:text-red-500 text-sm transition-colors">
                Вийти з акаунту
            </button>
        </form>
    </div>
</div>
@endsection
