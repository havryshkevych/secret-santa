@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-display text-santa-dark">Нова гра</h1>
        <span class="text-sm text-gray-400">Крок 1 з 3</span>
    </div>

    <form action="{{ route('game.store') }}" method="POST">
        @csrf
        
        <div class="mb-6">
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Назва гри (необов'язково)</label>
            <input type="text" name="title" id="title" placeholder="напр. Корпоратив 2025" 
                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 transition-colors">
        </div>

        <div class="mb-6">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Опис гри (необов'язково)</label>
            <textarea name="description" id="description" rows="3" placeholder="напр. Бюджет подарунка: до 500 грн. Обмін відбудеться 25 грудня." 
                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 transition-colors"></textarea>
        </div>

        <div class="mb-8">
            <label for="participants" class="block text-sm font-semibold text-gray-700 mb-1">Учасники</label>
            <p class="text-xs text-gray-500 mb-2">Введіть по одному імені на рядок. Можна додати Telegram @username.</p>
            <textarea name="participants" id="participants" rows="8" 
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-santa-green focus:ring focus:ring-santa-green focus:ring-opacity-20 font-mono text-sm"
                placeholder="Аліса&#10;Боб @bob_santa&#10;Чарлі"></textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary px-6 py-2 rounded-lg font-semibold flex items-center">
                Далі: Обмеження <span class="ml-2">&rarr;</span>
            </button>
        </div>
    </form>
</div>
@endsection
