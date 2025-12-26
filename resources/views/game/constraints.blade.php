@extends('layouts.app')

@section('content')
    <div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-display text-santa-dark">{{ __('constraints.title') }}</h1>
        <span class="text-sm text-gray-400">{{ __('constraints.step_2_3') }}</span>
    </div>
 
    <p class="mb-6 text-gray-600">{!! __('constraints.description') !!}</p>

    <form action="{{ route('game.storeConstraints', $game->id) }}" method="POST">
        @csrf
        
        <div class="mb-8 space-y-4">
            @foreach($game->participants as $participant)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-100 flex justify-between items-center text-xs font-bold uppercase tracking-wider text-gray-500">
                        <span>{{ __('constraints.santa_label') }}</span>
                    </div>
                    <div class="p-4">
                        <div class="font-bold text-santa-dark text-lg mb-4 border-b pb-2">
                            {{ $participant->name }}
                        </div>
                        <div class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('constraints.cannot_gift_label') }}</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($game->participants as $target)
                                @if($participant->id !== $target->id)
                                    <label class="inline-flex items-center space-x-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 cursor-pointer hover:border-santa-red transition-all select-none">
                                        <input type="checkbox" 
                                               name="constraints[{{ $participant->id }}][]" 
                                               value="{{ $target->id }}"
                                               class="text-santa-red rounded focus:ring-santa-red w-4 h-4">
                                        <span class="text-sm text-gray-700">{{ $target->name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('game.edit', $game->id) }}" class="text-gray-500 hover:text-santa-dark underline text-sm">{{ __('constraints.back_btn') }}</a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold flex items-center shadow-lg transition-colors">
                {{ __('constraints.generate_btn') }} <span class="ml-2">🎲</span>
            </button>
        </div>
    </form>
</div>
@endsection
