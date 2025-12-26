@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ showDeleteModal: false, deleteFormId: null, deleteGameTitle: '' }">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-display text-santa-red">{{ __('admin.title') }}</h1>
        <div class="text-sm text-gray-500">
            {{ __('admin.status_online') }} <span class="text-green-500 font-bold">{{ __('admin.online') }}</span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">{{ __('admin.stat_total_games') }}</p>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_games'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">{{ __('admin.stat_participants') }}</p>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_participants'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">{{ __('admin.stat_bot_games') }}</p>
            <p class="text-3xl font-bold text-santa-green">{{ $stats['bot_games'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">{{ __('admin.stat_assignments') }}</p>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_assignments'] }}</p>
        </div>
    </div>

    <!-- Games Table -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">{{ __('admin.recent_games') }}</h2>
            @if(session('status'))
                <span class="text-sm text-green-600 font-medium">{{ session('status') }}</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-400 text-xs uppercase font-bold">
                        <th class="px-6 py-4">{{ __('admin.name_label') }}</th>
                        <th class="px-6 py-4">{{ __('admin.participants_label') }}</th>
                        <th class="px-6 py-4">{{ __('admin.source_label') }}</th>
                        <th class="px-6 py-4">{{ __('admin.date_label') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('admin.actions_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    @forelse($games as $game)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-700">
                                {{ $game->title ?? __('admin.no_name') }}
                                <span class="text-xs text-gray-300 ml-2">#{{ $game->id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $game->participants_count }} {{ __('admin.persons_suffix') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 italic text-gray-500">
                                {{ $game->organizer_chat_id ? __('admin.source_bot') : __('admin.source_web') }}
                            </td>
                            <td class="px-6 py-4 text-gray-400">
                                {{ $game->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <a href="{{ route('game.result', $game) }}" 
                                   class="p-2 text-santa-green hover:bg-santa-mist rounded-lg transition-colors"
                                   title="{{ __('admin.view_title') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <form action="{{ route('admin.game.destroy', $game) }}" method="POST" class="hidden" id="delete-form-{{ $game->id }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" 
                                    @click="showDeleteModal = true; deleteFormId = 'delete-form-{{ $game->id }}'; deleteGameTitle = '{{ addslashes($game->title ?? __('admin.no_name')) }} #{{ $game->id }}'"
                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                    title="{{ __('admin.delete_title') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v4m5 0H8" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">{{ __('admin.no_games_yet') }}</td>
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
         class="fixed inset-0 z-[200] flex items-center justify-center bg-black/90 backdrop-blur-sm"
         @keydown.escape.window="showDeleteModal = false"
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4 text-center opacity-100"
             @click.outside="showDeleteModal = false">
             <div class="text-6xl mb-4">⚠️</div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('admin.delete_modal_title') }}</h3>
            <p class="text-gray-600 mb-2">{{ __('admin.delete_confirm_text') }}</p>
            <p class="font-semibold text-santa-red mb-4" x-text="deleteGameTitle"></p>
            <p class="text-sm text-gray-500 mb-6">{{ __('admin.delete_warning') }}</p>
            <div class="flex gap-3 justify-center">
                <button @click="showDeleteModal = false" 
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                    {{ __('admin.cancel_btn') }}
                </button>
                <button @click="document.getElementById(deleteFormId).submit()" 
                    class="px-6 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors">
                    {{ __('admin.delete_confirm_btn') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
