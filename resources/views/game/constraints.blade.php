@extends('layouts.app')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-display text-santa-dark">Хто кому не дарує?</h1>
        <span class="text-sm text-gray-400">Крок 2 з 3</span>
    </div>

    <p class="mb-6 text-gray-600">Відмітьте учасників, які <strong>не можуть</strong> дарувати подарунки один одному (напр. подружжя).</p>

    <form action="{{ route('game.storeConstraints', $game->id) }}" method="POST">
        @csrf
        
        <div class="overflow-x-auto mb-8 bg-gray-50 rounded-lg p-2 md:p-4">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="p-3 border-b text-sm font-bold text-gray-500 w-1/4">Санта (дарує)</th>
                        <th class="p-3 border-b text-sm font-bold text-gray-500">Не може дарувати...</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($game->participants as $participant)
                    <tr class="hover:bg-white transition-colors">
                        <td class="p-3 font-semibold text-santa-dark align-top border-b border-gray-100">
                            {{ $participant->name }}
                        </td>
                        <td class="p-3 border-b border-gray-100">
                            <div class="flex flex-wrap gap-2">
                                @foreach($game->participants as $target)
                                    @if($participant->id !== $target->id)
                                        <label class="inline-flex items-center space-x-2 bg-white border border-gray-200 rounded px-2 py-1 cursor-pointer hover:border-santa-red transition-all">
                                            <input type="checkbox" 
                                                   name="constraints[{{ $participant->id }}][]" 
                                                   value="{{ $target->id }}"
                                                   class="text-santa-red rounded focus:ring-santa-red">
                                            <span class="text-sm">{{ $target->name }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('game.create') }}" class="text-gray-500 hover:text-santa-dark underline text-sm">Назад</a>
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-semibold flex items-center shadow-lg">
                Згенерувати пари <span class="ml-2">🎲</span>
            </button>
        </div>
    </form>
</div>
@endsection
