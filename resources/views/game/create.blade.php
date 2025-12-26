@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-display text-santa-dark">{{ __('game.new_game_title') }}</h1>
        <span class="text-sm text-gray-400">{{ __('game.step_1_3') }}</span>
    </div>

    <form action="{{ route('game.store') }}" method="POST">
        @csrf
        
        <div class="mb-6">
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('game.title_label_optional') }}</label>
            <input type="text" name="title" id="title" placeholder="{{ __('game.ph_title') }}" 
                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 transition-colors">
        </div>

        <div class="mb-6">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('game.description_label_optional') }}</label>
            <textarea name="description" id="description" rows="3" placeholder="{{ __('game.ph_description_example') }}" 
                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 transition-colors"></textarea>
        </div>

        <div class="mb-8">
            <label for="participants" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('game.participants_label_optional') }}</label>
            <p class="text-xs text-gray-500 mb-2">{{ __('game.participants_help_optional') }}</p>
            <textarea name="participants" id="participants" rows="8"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 font-mono text-sm"
                placeholder="{{ __('game.ph_participants_optional') }}"></textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-santa-green hover:bg-green-600 text-white px-8 py-3 rounded-lg font-semibold flex items-center shadow-lg transition-all">
                {{ __('game.create_game_button') }} <span class="ml-2">🎄</span>
            </button>
        </div>
    </form>
</div>
@endsection
