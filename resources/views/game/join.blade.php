@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">🎅</div>
            <h1 class="text-3xl font-display text-santa-dark mb-2">{{ $game->title ?? 'Secret Santa' }}</h1>
            @if($game->description)
                <p class="text-gray-600 mt-2">{{ $game->description }}</p>
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
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-center">
                ✅ {{ __('game.you_already_joined') }}
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
                <p class="text-gray-600 mb-4">{{ __('game.login_to_join') }}</p>
                <a href="{{ route('login.telegram') }}?redirect={{ urlencode(route('game.join', $game->join_token)) }}"
                   class="inline-block btn-primary px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                    {{ __('auth.login_telegram') }}
                </a>
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
</script>
@endsection
