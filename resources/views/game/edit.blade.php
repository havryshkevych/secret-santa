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
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800">👥 {{ __('game.participants') }} ({{ $game->participants->count() }})</h3>
            @if(!$game->is_started)
                <button onclick="document.getElementById('addParticipantForm').classList.toggle('hidden')"
                    class="text-sm bg-santa-green text-white px-4 py-2 rounded-lg hover:bg-santa-dark transition-colors">
                    ➕ {{ __('game.add_participant') }}
                </button>
            @endif
        </div>

        <!-- Add Participant Form (Hidden by default) -->
        @if(!$game->is_started)
            <div id="addParticipantForm" class="hidden mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <form action="{{ route('game.addParticipant', $game->id) }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('game.participant_name') }}</label>
                            <input type="text" name="name" required
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-santa-green focus:border-santa-green"
                                placeholder="{{ __('game.participant_name_placeholder') }}">
                            <p class="text-xs text-gray-500 mt-1">{{ __('game.participant_without_telegram_hint') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-santa-green text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-santa-dark transition-colors">
                                {{ __('game.add_btn') }}
                            </button>
                            <button type="button" onclick="document.getElementById('addParticipantForm').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-300 transition-colors">
                                {{ __('game.cancel_btn') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        @if($game->participants->count() > 0)
            <div class="space-y-2">
                @foreach($game->participants as $participant)
                    <div class="flex items-center justify-between gap-2 text-sm text-gray-700 p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span>✓</span>
                            @php
                                $isUsernameAsName = $participant->name === '@' . $participant->telegram_username ||
                                                    $participant->name === $participant->telegram_username;
                                $hasTelegram = !empty($participant->telegram_chat_id) || !empty($participant->telegram_username);
                            @endphp
                            <span class="font-semibold">{{ $participant->name }}</span>
                            @if($participant->telegram_username && !$isUsernameAsName)
                                <span class="text-gray-500">{{ '@' . $participant->telegram_username }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($hasTelegram)
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">📱 Telegram</span>
                            @else
                                <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded">👤 {{ __('game.no_telegram') }}</span>
                            @endif
                        </div>
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
            <a href="{{ route('game.constraints', $game->id) }}" class="block w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:bg-green-700 transition-colors text-center">
                🎅 {{ __('game.start_game_btn') }}
            </a>
        </div>
    @elseif($game->is_started)
        <div class="bg-blue-50 border border-blue-200 rounded-3xl p-6 mb-6 text-center">
            <p class="text-blue-800 font-semibold">✅ {{ __('game.game_started') }}</p>
            <a href="{{ route('game.result', $game->id) }}" class="inline-block mt-3 text-blue-600 hover:underline font-semibold">
                {{ __('game.view_results') }} →
            </a>
        </div>

        <!-- Notify Players Button -->
        <div class="bg-white rounded-3xl shadow-lg border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-3">📢 {{ __('game.notify_players') }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ __('game.notify_players_description') }}</p>
            <form action="{{ route('game.notifyPlayers', $game->id) }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-[#0088cc] hover:bg-[#006699] text-white py-3 rounded-xl font-semibold shadow-md transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    {{ __('game.notify_all_players') }}
                </button>
            </form>
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
