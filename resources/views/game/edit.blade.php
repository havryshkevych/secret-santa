@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-display text-santa-dark">{{ __('game.settings_title') }}</h1>
        <a href="{{ route('game.myGames') }}" class="text-gray-500 hover:text-gray-700 font-semibold text-sm">
            &larr; {{ __('game.back_to_games') }}
        </a>
    </div>

    <!-- Join Link Section -->
    @if($game->join_token)
        <div class="bg-santa-mist rounded-3xl p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-3">📎 {{ __('game.invite_link') }}</h3>
            <p class="text-sm text-gray-600 mb-3">{{ __('game.share_link_instruction') }}</p>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ route('game.join', $game->join_token) }}"
                       class="flex-1 px-3 py-2 text-sm bg-white border border-gray-200 rounded-lg"
                       id="joinLink">
                <button onclick="copyLink()" class="px-4 py-2 bg-santa-gold text-white rounded-lg text-sm font-semibold hover:opacity-90">
                    {{ __('game.copy_link') }}
                </button>
            </div>
        </div>
    @endif

    <!-- Participants List -->
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 p-8 mb-6">
        <h3 class="font-bold text-gray-800 mb-4">👥 {{ __('game.participants') }} ({{ $game->participants->count() }})</h3>
        @if($game->participants->count() > 0)
            <div class="space-y-2">
                @foreach($game->participants as $participant)
                    <div class="flex items-center gap-2 text-sm text-gray-700 p-3 bg-gray-50 rounded-lg">
                        <span>✓</span>
                        @php
                            $isUsernameAsName = $participant->name === '@' . $participant->telegram_username ||
                                                $participant->name === $participant->telegram_username;
                        @endphp
                        <span class="font-semibold">{{ $participant->name }}</span>
                        @if($participant->telegram_username && !$isUsernameAsName)
                            <span class="text-gray-500">{{ '@' . $participant->telegram_username }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 italic text-center py-4">{{ __('game.no_participants_yet') }}</p>
        @endif
    </div>

    <!-- Start Game Button -->
    @if(!$game->is_started && $game->participants->count() >= 3)
        <div class="bg-green-50 border border-green-200 rounded-3xl p-6 mb-6">
            <h3 class="font-bold text-green-800 mb-2">🎄 {{ __('game.ready_to_start') }}</h3>
            <p class="text-sm text-green-700 mb-4">{{ __('game.start_game_info') }}</p>
            <form action="{{ route('game.start', $game->id) }}" method="POST" onsubmit="return confirm('{{ __('game.confirm_start') }}')">
                @csrf
                <button type="submit" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:bg-green-700 transition-colors">
                    🎅 {{ __('game.start_game_btn') }}
                </button>
            </form>
        </div>
    @elseif($game->is_started)
        <div class="bg-blue-50 border border-blue-200 rounded-3xl p-6 mb-6 text-center">
            <p class="text-blue-800 font-semibold">✅ {{ __('game.game_started') }}</p>
            <a href="{{ route('game.result', $game->id) }}" class="inline-block mt-3 text-blue-600 hover:underline font-semibold">
                {{ __('game.view_results') }} →
            </a>
        </div>
    @elseif($game->participants->count() < 3)
        <div class="bg-yellow-50 border border-yellow-200 rounded-3xl p-6 mb-6 text-center">
            <p class="text-yellow-800">⏳ {{ __('game.waiting_participants') }}</p>
            <p class="text-sm text-yellow-600 mt-1">{{ __('game.need_min_participants') }}</p>
        </div>
    @endif

    <!-- Settings Form -->
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 p-8">
        <h3 class="font-bold text-gray-800 mb-6">⚙️ {{ __('game.game_settings') }}</h3>
        <form action="{{ route('game.update', $game->id) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="mb-6">
                <label for="title" class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">{{ __('game.title_label') }}</label>
                <input type="text" name="title" id="title" value="{{ old('title', $game->title) }}"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-santa-red focus:border-santa-red outline-none transition-all"
                    required>
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label for="description" class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">{{ __('game.description_label') }}</label>
                <textarea name="description" id="description" rows="5"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-santa-green focus:border-santa-green outline-none transition-all"
                    placeholder="{{ __('game.ph_description') }}">{{ old('description', $game->description) }}</textarea>
                <p class="text-gray-400 text-xs mt-2 italic">{{ __('game.description_help') }}</p>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-4">
                <button type="submit" class="w-full btn-primary py-4 rounded-xl font-bold text-lg shadow-lg transform transition hover:scale-[1.02]">
                    {{ __('game.save_changes') }} ✨
                </button>

                <div class="flex justify-between items-center text-sm pt-4 border-t border-gray-100 mt-4">
                    <a href="{{ route('game.constraints', $game->id) }}" class="text-santa-green font-bold hover:underline">
                        ⚙️ {{ __('game.manage_constraints') }}
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script>
    function copyLink() {
        const input = document.getElementById('joinLink');
        input.select();
        document.execCommand('copy');
        alert('{{ __('game.link_copied') }}');
    }
    </script>
</div>
@endsection
