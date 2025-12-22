@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ showDeleteModal: false, deleteFormId: null, deleteGameTitle: '' }">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-display text-santa-red">Панель адміністратора</h1>
        <div class="text-sm text-gray-500">
            Статус: <span class="text-green-500 font-bold">Онлайн</span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Всього ігор</p>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_games'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Учасників</p>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_participants'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Ігри з боту</p>
            <p class="text-3xl font-bold text-santa-green">{{ $stats['bot_games'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Призначень</p>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_assignments'] }}</p>
        </div>
    </div>

    <!-- Games Table -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Останні ігри</h2>
            @if(session('status'))
                <span class="text-sm text-green-600 font-medium">{{ session('status') }}</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-400 text-xs uppercase font-bold">
                        <th class="px-6 py-4">Назва</th>
                        <th class="px-6 py-4">Учасники</th>
                        <th class="px-6 py-4">Джерело</th>
                        <th class="px-6 py-4">Дата</th>
                        <th class="px-6 py-4 text-right">Дії</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    @forelse($games as $game)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-700">
                                {{ $game->title ?? 'Без назви' }}
                                <span class="text-xs text-gray-300 ml-2">#{{ $game->id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $game->participants_count }} осіб
                                </span>
                            </td>
                            <td class="px-6 py-4 italic text-gray-500">
                                {{ $game->organizer_chat_id ? 'Telegram бот' : 'Веб-інтерфейс' }}
                            </td>
                            <td class="px-6 py-4 text-gray-400">
                                {{ $game->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <a href="{{ route('game.result', $game) }}" 
                                   class="text-santa-green hover:text-santa-dark font-bold text-xs uppercase tracking-widest">
                                    Переглянути
                                </a>
                                <form action="{{ route('admin.game.destroy', $game) }}" method="POST" class="inline" id="delete-form-{{ $game->id }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" 
                                    @click="showDeleteModal = true; deleteFormId = 'delete-form-{{ $game->id }}'; deleteGameTitle = '{{ addslashes($game->title ?? 'Без назви') }} #{{ $game->id }}'"
                                    class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-widest">
                                    Видалити
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">Ігор поки немає.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            {{ $games->links() }}
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="showDeleteModal = false"
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4 text-center"
             @click.outside="showDeleteModal = false">
            <div class="text-6xl mb-4">⚠️</div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Видалити гру?</h3>
            <p class="text-gray-600 mb-2">Ви збираєтесь видалити:</p>
            <p class="font-semibold text-santa-red mb-4" x-text="deleteGameTitle"></p>
            <p class="text-sm text-gray-500 mb-6">Це назавжди видалить усіх учасників, призначення та дані. Цю дію не можна скасувати.</p>
            <div class="flex gap-3 justify-center">
                <button @click="showDeleteModal = false" 
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                    Скасувати
                </button>
                <button @click="document.getElementById(deleteFormId).submit()" 
                    class="px-6 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors">
                    Так, видалити
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
