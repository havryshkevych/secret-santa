@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ editing: false }">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-display text-santa-red mb-2">Хо-хо-хо! Готово! 🎅</h1>
        <p class="text-gray-600 text-lg">Пари Таємного Санти успішно згенеровані.</p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 rounded shadow-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('pins'))
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 rounded shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong class="font-bold">ВАЖЛИВО:</strong> Скопіюйте посилання та PIN-коди зараз. 
                    З міркувань безпеки, PIN-коди <span class="underline">не будуть</span> показані знову після виходу з цієї сторінки.
                </p>
            </div>
        </div>
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
                        <button @click="editing = true" class="text-xs text-santa-green hover:underline mt-2">Редагувати назву та опис</button>
                    </div>
                    <button onclick="window.print()" class="text-sm text-santa-green hover:text-santa-dark font-medium flex items-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Друк / PDF
                    </button>
                </div>
            </template>

            <template x-if="editing">
                <form action="{{ route('game.update', $game) }}" method="POST" @keydown.escape.window="editing = false">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <label for="title" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Назва гри</label>
                            <input type="text" name="title" id="title" value="{{ $game->title }}" 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 text-sm">
                        </div>
                        <div>
                            <label for="description" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Опис гри</label>
                            <textarea name="description" id="description" rows="2" 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 text-sm">{{ $game->description }}</textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="editing = false" class="text-sm text-gray-500 hover:text-gray-700">Скасувати</button>
                            <button type="submit" class="bg-santa-green text-white px-4 py-1.5 rounded-lg text-sm font-semibold hover:bg-santa-dark transition-colors">Зберегти</button>
                        </div>
                    </div>
                </form>
            </template>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($game->participants as $participant)
                <div class="p-6 hover:bg-gray-50 transition-colors group">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="md:w-1/4">
                            <h3 class="text-lg font-bold text-gray-900">{{ $participant->name }}</h3>
                        </div>
                        
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Посилання</span>
                                <div class="flex-1 flex rounded-md shadow-sm">
                                    <input type="text" readonly 
                                        value="{{ route('reveal.show', ['gameId' => $game->id, 'participantId' => $participant->id, 'token' => $participant->reveal_token]) }}" 
                                        class="flex-1 min-w-0 block w-full px-3 py-1.5 rounded-none rounded-l-md text-sm border-gray-300 bg-gray-50 text-gray-600 focus:ring-santa-green focus:border-santa-green truncate">
                                    <button onclick="copyToClipboard(this.previousElementSibling.value)" 
                                        class="inline-flex items-center px-3 py-1.5 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 text-sm hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-santa-green focus:border-santa-green transition-colors"
                                        title="Копіювати">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="md:w-32 flex items-center justify-end">
                            <div class="text-right">
                                <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">PIN-код</span>
                                @if(session('pins') && isset(session('pins')[$participant->id]))
                                    <span class="text-2xl font-mono font-bold text-santa-red tracking-widest bg-red-50 px-2 py-1 rounded select-all selection:bg-red-200">
                                        {{ session('pins')[$participant->id] }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400 italic">Приховано</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-center">
            <a href="{{ route('home') }}" class="text-santa-green hover:underline text-sm font-medium">Створити нову гру</a>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Посилання скопійовано! Надішліть його учаснику.');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
</script>
@endsection
