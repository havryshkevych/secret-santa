@extends('layouts.app')

@section('content')
<div class="text-center py-4">
    <div class="mb-8 flex justify-center">
        <!-- Minimal SVG Icon -->
        <svg class="w-24 h-24 text-santa-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
        </svg>
    </div>

    <h1 class="text-4xl font-display text-santa-dark mb-4">Хо-Хо-Хо!</h1>
    <p class="text-gray-600 mb-8 max-w-lg mx-auto">
        Створюйте нову гру, запрошуйте друзів і нехай почнеться магія. 
        Ніхто не дізнається, хто його Secret Santa, до самого розкриття! 🎄
    </p>

    <a href="{{ route('game.create') }}" class="btn-primary inline-flex items-center px-8 py-3 rounded-full text-lg font-semibold shadow-lg mb-12">
        <span class="mr-2">🎁</span> Почати нову гру
    </a>

    <!-- How It Works Section -->
    <div class="mt-8 border-t border-gray-200 pt-10">
        <h3 class="text-2xl font-display text-santa-green mb-8">Як це працює?</h3>
        
        <div class="grid md:grid-cols-3 gap-6 text-left">
            <!-- Step 1 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">📝</div>
                <h4 class="font-bold text-gray-800 mb-2">1. Створи гру</h4>
                <p class="text-gray-600 text-sm">
                    Введи назву та список учасників. Можеш вказати Telegram-юзернейми для зручних сповіщень.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🚫</div>
                <h4 class="font-bold text-gray-800 mb-2">2. Налаштуй обмеження</h4>
                <p class="text-gray-600 text-sm">
                    Вкажи, хто кому не може дарувати — наприклад, подружжя або родичі. Все буде враховано!
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🎯</div>
                <h4 class="font-bold text-gray-800 mb-2">3. Запусти жеребкування</h4>
                <p class="text-gray-600 text-sm">
                    Алгоритм випадково розподілить учасників. Кожен отримає унікальне посилання з PIN-кодом.
                </p>
            </div>

            <!-- Step 4 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🔗</div>
                <h4 class="font-bold text-gray-800 mb-2">4. Розішли посилання</h4>
                <p class="text-gray-600 text-sm">
                    Учасники переходять за посиланням, вводять PIN і дізнаються кому готувати подарунок.
                </p>
            </div>

            <!-- Step 5 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">📱</div>
                <h4 class="font-bold text-gray-800 mb-2">5. Або через Telegram</h4>
                <p class="text-gray-600 text-sm">
                    Учасники можуть авторизуватися через нашого бота <strong>@little_santa_bot</strong> — без PIN-коду!
                </p>
            </div>

            <!-- Step 6 -->
            <div class="bg-white/80 rounded-2xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">✨</div>
                <h4 class="font-bold text-gray-800 mb-2">6. Wishlist</h4>
                <p class="text-gray-600 text-sm">
                    Кожен учасник може додати побажання до подарунку — Санта побачить їх на сторінці результату!
                </p>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="mt-12 border-t border-gray-200 pt-10">
        <h3 class="text-2xl font-display text-santa-green mb-8">Чому обрати нас?</h3>
        
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">🆓</div>
                <p class="text-gray-700 font-semibold text-sm">100% Безкоштовно</p>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">📵</div>
                <p class="text-gray-700 font-semibold text-sm">Без реєстрації</p>
            </div>
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">🤖</div>
                <p class="text-gray-700 font-semibold text-sm">Telegram бот</p>
            </div>
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 text-center">
                <div class="text-3xl mb-2">🎲</div>
                <p class="text-gray-700 font-semibold text-sm">Справедливий розподіл</p>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="mt-12 pt-8">
        <a href="{{ route('game.create') }}" class="btn-primary inline-flex items-center px-10 py-4 rounded-full text-xl font-bold shadow-xl transform hover:scale-105 transition-transform">
            <span class="mr-3">🎅</span> Почати зараз!
        </a>
        <p class="text-gray-500 text-sm mt-4">Готові до свят? Це займе лише 2 хвилини!</p>
    </div>
</div>
@endsection
