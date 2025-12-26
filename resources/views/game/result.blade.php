@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ editing: false }">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-display text-santa-red mb-2">{{ __('result.title') }}</h1>
        <p class="text-gray-600 text-lg">{{ __('result.subtitle') }}</p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 rounded shadow-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <template x-if="!editing">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-center md:text-left">
                        <h2 class="text-xl font-semibold text-gray-800">{{ $game->title ?? 'Secret Santa' }}</h2>
                        @if($game->description)
                            <p class="text-sm text-gray-500 mt-1 italic">{{ $game->description }}</p>
                        @endif
                        <button @click="editing = true" class="text-xs text-santa-green hover:underline mt-2">{{ __('result.edit_title_desc') }}</button>
                    </div>
                    <button onclick="window.print()" class="text-sm text-santa-green hover:text-santa-dark font-medium flex items-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        {{ __('result.print_btn') }}
                    </button>
                </div>
            </template>

            <template x-if="editing">
                <form action="{{ route('game.update', $game) }}" method="POST" @keydown.escape.window="editing = false">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <label for="title" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">{{ __('result.game_title_label') }}</label>
                            <input type="text" name="title" id="title" value="{{ $game->title }}" 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 text-sm">
                        </div>
                        <div>
                            <label for="description" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">{{ __('result.game_desc_label') }}</label>
                            <textarea name="description" id="description" rows="2" 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 text-sm">{{ $game->description }}</textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="editing = false" class="text-sm text-gray-500 hover:text-gray-700">{{ __('constraints.back_btn') }}</button>
                            <button type="submit" class="bg-santa-green text-white px-4 py-1.5 rounded-lg text-sm font-semibold hover:bg-santa-dark transition-colors">{{ __('result.save_btn') }}</button>
                        </div>
                    </div>
                </form>
            </template>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($game->participants as $participant)
                <div class="p-6 hover:bg-gray-50 transition-colors group">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ $participant->name }}</h3>
                                @if($participant->telegram_chat_id)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800" title="{{ __('result.has_telegram') }}">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                                        </svg>
                                        Telegram
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800" title="{{ __('result.no_telegram') }}">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ __('result.send_manually') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($participant->reveal_token)
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                                    {{ __('result.link_label') }}
                                </label>
                                <div class="flex rounded-lg shadow-sm border border-gray-300 overflow-hidden">
                                    <input type="text" readonly
                                        value="{{ route('reveal.show', ['gameId' => $game->id, 'participantId' => $participant->id, 'token' => $participant->reveal_token]) }}"
                                        class="flex-1 min-w-0 block w-full px-3 py-2 text-sm bg-gray-50 text-gray-700 focus:ring-santa-green focus:border-santa-green border-0 truncate">
                                    <button onclick="copyToClipboard(this.previousElementSibling.value)"
                                        class="inline-flex items-center px-4 py-2 border-l border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-santa-green hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-santa-green transition-colors"
                                        title="{{ __('result.copy_btn') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span class="ml-2 hidden sm:inline">{{ __('result.copy_btn') }}</span>
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-center">
            <a href="{{ route('home') }}" class="text-santa-green hover:underline text-sm font-medium">{{ __('result.create_new_game') }}</a>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('{{ __('result.link_copied') }}');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
</script>
@endsection
