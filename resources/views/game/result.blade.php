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
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-3">{{ $participant->name }}</h3>
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
