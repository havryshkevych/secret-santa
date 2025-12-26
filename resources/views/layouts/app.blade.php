<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Secret Santa') }}</title>
    <meta name="description" content="{{ __('app.meta_description') }}">

    @yield('meta')
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Marck+Script&family=Mountains+of+Christmas:wght@400;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Howler.js for audio -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js"></script>

    <!-- Register Service Worker for audio caching -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('Service Worker registered successfully:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
    </script>

    <script>
        // Telegram Web App Auto-Login
        document.addEventListener('DOMContentLoaded', function() {
                if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initData) {
                window.Telegram.WebApp.expand();
                const initData = window.Telegram.WebApp.initData;

                // Only try to login if not already logged in AND haven't tried before
                @guest
                    // Prevent infinite reload loop
                    const loginAttempted = sessionStorage.getItem('tg_login_attempted');
                    if (!loginAttempted) {
                        sessionStorage.setItem('tg_login_attempted', 'true');

                        fetch('{{ route("login.telegram.webapp") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ initData: initData })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Use href instead of reload to ensure session is updated
                                window.location.href = window.location.href;
                            } else {
                                sessionStorage.removeItem('tg_login_attempted');
                            }
                        })
                        .catch(error => {
                            console.error('Telegram WebApp login error:', error);
                            sessionStorage.removeItem('tg_login_attempted');
                        });
                    }
                @else
                    // Clear the flag when user is authenticated
                    sessionStorage.removeItem('tg_login_attempted');
                @endguest

                // Inform the app that we are in a Mini App
                document.body.classList.add('is-mini-app');
            }
        });

        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        santa: {
                            red: '#D42426',
                            dark: '#16302B',
                            green: '#2E5B4B',
                            gold: '#F0C258',
                            snow: '#F8F9FA',
                            mist: '#E8F1F2'
                        }
                    },
                    fontFamily: {
                        display: ['"Marck Script"', 'cursive'],
                        body: ['"Nunito"', 'sans-serif'],
                        santa: ['"Mountains of Christmas"', 'cursive'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #16302B;
            background-image: radial-gradient(#2E5B4B 1px, transparent 1px);
            background-size: 20px 20px;
            color: #F8F9FA;
        }

        /* Alpine.js cloak */
        [x-cloak] {
            display: none !important;
        }

        /* Animated snowflakes - more lively */
        .snowflake {
            position: fixed;
            color: white;
            top: -20px;
            pointer-events: none;
            z-index: 1;
            animation: fall linear infinite, sway ease-in-out infinite;
        }
        @keyframes fall {
            to { transform: translateY(105vh); }
        }
        @keyframes sway {
            0%, 100% { margin-left: 0; }
            25% { margin-left: 15px; }
            75% { margin-left: -15px; }
        }
        
        .snow-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            color: #1a202c;
        }
        
        .btn-primary {
            background-color: #D42426;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #B0181A;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 36, 38, 0.4);
        }
        
        /* Gold text glow with smooth black outline using shadows */
        .text-glow-gold {
            text-shadow:
                /* Black outline - 8 directions for smooth edges */
                -1px -1px 0 #000,
                1px -1px 0 #000,
                -1px 1px 0 #000,
                1px 1px 0 #000,
                -1px 0 0 #000,
                1px 0 0 #000,
                0 -1px 0 #000,
                0 1px 0 #000,
                /* Golden glow */
                0 0 30px rgba(240, 194, 88, 0.5),
                /* Depth shadow */
                0 2px 4px rgba(0,0,0,0.3);
        }

        /* Slightly thicker outline for larger screens */
        @media (min-width: 768px) {
            .text-glow-gold {
                text-shadow:
                    /* Black outline - thicker */
                    -2px -2px 0 #000,
                    2px -2px 0 #000,
                    -2px 2px 0 #000,
                    2px 2px 0 #000,
                    -2px 0 0 #000,
                    2px 0 0 #000,
                    0 -2px 0 #000,
                    0 2px 0 #000,
                    /* Diagonal for smoother edges */
                    -1px -1px 0 #000,
                    1px -1px 0 #000,
                    -1px 1px 0 #000,
                    1px 1px 0 #000,
                    /* Golden glow */
                    0 0 30px rgba(240, 194, 88, 0.5),
                    /* Depth shadow */
                    0 3px 6px rgba(0,0,0,0.3);
            }
        }
        
        /* New Year Garland Styles */
        .b-page__content{min-height:200px}
        .b-head-decor{display:none}
        .b-page_newyear .b-head-decor{
           position:fixed;
           top:0;
           left:0;
           display:block;
           height:115px;
           width:100%;
           overflow:hidden;
           background:url(/garland/balls/b-head-decor_newyear.png) repeat-x 0 0;
           z-index:100;
        }
        .b-page_newyear .b-head-decor__inner{position:absolute;top:0;left:0;height:115px;display:block;width:373px}
        .b-page_newyear .b-head-decor::before{content:'';display:block;position:absolute;top:-115px;left:0;z-index:3;height:115px;display:block;width:100%;box-shadow:0 15px 30px rgba(0,0,0,0.75)}
        .b-page_newyear .b-head-decor__inner_n2{left:373px}
        .b-page_newyear .b-head-decor__inner_n3{left:746px}
        .b-page_newyear .b-head-decor__inner_n4{left:1119px}
        .b-page_newyear .b-head-decor__inner_n5{left:1492px}
        .b-page_newyear .b-head-decor__inner_n6{left:1865px}
        .b-page_newyear .b-head-decor__inner_n7{left:2238px}

        /* Scale garland for mobile / mini app */
        /* Scale garland for mobile / mini app */
        @media (max-width: 640px) {
            .b-page_newyear .b-head-decor {
                overflow: visible;
                height: 57px; /* Half of original 115px */
                background-size: auto 57px; /* Scale background image height to 50% */
            }
            .b-page_newyear .b-head-decor__inner {
                transform: scale(0.5); /* Smaller scale for mobile */
                transform-origin: 0 0;
            }
            /* Adjust positions for scaled elements to prevent gaps */
            .b-page_newyear .b-head-decor__inner_n2{left:186.5px}  /* 373 * 0.5 */
            .b-page_newyear .b-head-decor__inner_n3{left:373px}    /* 746 * 0.5 */
            .b-page_newyear .b-head-decor__inner_n4{left:559.5px}  /* 1119 * 0.5 */
            .b-page_newyear .b-head-decor__inner_n5{left:746px}    /* 1492 * 0.5 */
            .b-page_newyear .b-head-decor__inner_n6{left:932.5px}  /* 1865 * 0.5 */
            .b-page_newyear .b-head-decor__inner_n7{left:1119px}   /* 2238 * 0.5 */
        }
        
        .is-mini-app .b-head-decor {
            opacity: 0.8;
            pointer-events: none; /* Don't interfere with top buttons */
        }

        .b-ball{position:absolute}
        .b-ball_n1{top:0;left:3px;width:59px;height:83px}
        .b-ball_n2{top:-19px;left:51px;width:55px;height:70px}
        .b-ball_n3{top:9px;left:88px;width:49px;height:67px}
        .b-ball_n4{top:0;left:133px;width:57px;height:102px}
        .b-ball_n5{top:0;left:166px;width:49px;height:57px}
        .b-ball_n6{top:6px;left:200px;width:54px;height:70px}
        .b-ball_n7{top:0;left:240px;width:56px;height:67px}
        .b-ball_n8{top:0;left:283px;width:54px;height:53px}
        .b-ball_n9{top:10px;left:321px;width:49px;height:66px}
        .b-ball_n1 .b-ball__i{background:url(/garland/balls/b-ball_n1.png) no-repeat}
        .b-ball_n2 .b-ball__i{background:url(/garland/balls/b-ball_n2.png) no-repeat}
        .b-ball_n3 .b-ball__i{background:url(/garland/balls/b-ball_n3.png) no-repeat}
        .b-ball_n4 .b-ball__i{background:url(/garland/balls/b-ball_n4.png) no-repeat}
        .b-ball_n5 .b-ball__i{background:url(/garland/balls/b-ball_n5.png) no-repeat}
        .b-ball_n6 .b-ball__i{background:url(/garland/balls/b-ball_n6.png) no-repeat}
        .b-ball_n7 .b-ball__i{background:url(/garland/balls/b-ball_n7.png) no-repeat}
        .b-ball_n8 .b-ball__i{background:url(/garland/balls/b-ball_n8.png) no-repeat}
        .b-ball_n9 .b-ball__i{background:url(/garland/balls/b-ball_n9.png) no-repeat}
        .b-ball_i1 .b-ball__i{background:url(/garland/balls/b-ball_i1.png) no-repeat}
        .b-ball_i2 .b-ball__i{background:url(/garland/balls/b-ball_i2.png) no-repeat}
        .b-ball_i3 .b-ball__i{background:url(/garland/balls/b-ball_i3.png) no-repeat}
        .b-ball_i4 .b-ball__i{background:url(/garland/balls/b-ball_i4.png) no-repeat}
        .b-ball_i5 .b-ball__i{background:url(/garland/balls/b-ball_i5.png) no-repeat}
        .b-ball_i6 .b-ball__i{background:url(/garland/balls/b-ball_i6.png) no-repeat}
        .b-ball_i1{top:0;left:0;width:25px;height:71px}
        .b-ball_i2{top:0;left:25px;width:61px;height:27px}
        .b-ball_i3{top:0;left:176px;width:29px;height:31px}
        .b-ball_i4{top:0;left:205px;width:50px;height:51px}
        .b-ball_i5{top:0;left:289px;width:78px;height:28px}
        .b-ball_i6{top:0;left:367px;width:6px;height:69px}
        .b-ball__i{
            position:absolute;
            width:100%;
            height:100%;
            transform-origin:50% 0;
            transition:all .3s ease-in-out;
            pointer-events:none
        }
        .b-ball_bounce .b-ball__right{position:absolute;top:0;right:0;left:50%;bottom:0;z-index:9}
        .b-ball_bounce:hover .b-ball__right{display:none}
        .b-ball_bounce .b-ball__right:hover{left:0;display:block!important}
        .b-ball_bounce.bounce>.b-ball__i{transform:rotate(-9deg)}
        .b-ball_bounce .b-ball__right.bounce+.b-ball__i{transform:rotate(9deg)}
        .b-ball_bounce.bounce1>.b-ball__i{transform:rotate(6deg)}
        .b-ball_bounce .b-ball__right.bounce1+.b-ball__i{transform:rotate(-6deg)}
        .b-ball_bounce.bounce2>.b-ball__i{transform:rotate(-3deg)}
        .b-ball_bounce .b-ball__right.bounce2+.b-ball__i{transform:rotate(3deg)}
        .b-ball_bounce.bounce3>.b-ball__i{transform:rotate(1.5deg)}
        .b-ball_bounce .b-ball__right.bounce3+.b-ball__i{transform:rotate(-1.5deg)}

        /* Hide specific elements in Telegram Mini App */
        .is-mini-app .tg-login-section {
            display: none;
        }

        .is-mini-app {
            padding-top: 5rem !important;
            padding-bottom: 1rem !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .is-mini-app .app-wrapper {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .is-mini-app .snow-card {
            padding: 0.75rem !important;
            width: 100% !important;
        }
        
        @media (max-width: 640px) {
            .is-mini-app header {
                margin-bottom: 1rem !important;
            }
            .snow-card {
                padding: 1.5rem !important;
                border-radius: 1rem !important;
            }
        }

        /* Pulse animation for music button when autoplay is blocked */
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        /* Angel floating animation when music is playing */
        @keyframes angelFloat {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-8px) rotate(-5deg);
            }
            50% {
                transform: translateY(-12px) rotate(0deg);
            }
            75% {
                transform: translateY(-8px) rotate(5deg);
            }
        }

        .angel-floating {
            animation: angelFloat 2.5s ease-in-out infinite;
        }

        /* Volume indicator */
        #volumeIndicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(22, 48, 43, 0.95);
            border: 2px solid #F0C258;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        #volumeIndicator.show {
            opacity: 1;
        }

        .volume-bar-container {
            width: 200px;
            height: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .volume-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #F0C258, #D42426);
            border-radius: 4px;
            transition: width 0.1s ease;
        }
    </style>
</head>
<body class="font-body min-h-screen flex items-center justify-center p-4 pt-32">

    @if(!session('locale') && (!auth()->check() || !auth()->user()->language))
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" id="language-modal">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center relative overflow-hidden opacity-100">
             <!-- Decorative elements -->
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-santa-red to-santa-green"></div>
            
            <h2 class="font-santa text-3xl mb-2 text-santa-dark">Welcome!</h2>
            <p class="text-gray-600 mb-8 font-body">Please choose your language<br>Будь ласка, оберіть мову</p>
            
            <div class="space-y-4">
                 <a href="{{ route('locale.switch', 'en') }}" class="block w-full py-4 rounded-xl border-2 border-santa-mist hover:border-santa-red hover:bg-santa-snow transition-all group">
                    <div class="text-2xl mb-1">🇬🇧</div>
                    <div class="font-bold text-santa-dark group-hover:text-santa-red">English</div>
                </a>
                
                <a href="{{ route('locale.switch', 'uk') }}" class="block w-full py-4 rounded-xl border-2 border-santa-mist hover:border-santa-green hover:bg-santa-snow transition-all group">
                    <div class="text-2xl mb-1">🇺🇦</div>
                    <div class="font-bold text-santa-dark group-hover:text-santa-green">Українська</div>
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- New Year Garland -->
    <div class="b-page_newyear">
        <div class="b-page__content">
        <i class="b-head-decor">
            <i class="b-head-decor__inner b-head-decor__inner_n1">
              <div class="b-ball b-ball_n1 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n2 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n3 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n4 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n5 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n6 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n7 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n8 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n9 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i1"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i2"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i3"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i4"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i5"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i6"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
            </i>
            <i class="b-head-decor__inner b-head-decor__inner_n2">
              <div class="b-ball b-ball_n1 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n2 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n3 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n4 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n5 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n6 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n7 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n8 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n9 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i1"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i2"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i3"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i4"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i5"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i6"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
            </i>
            <i class="b-head-decor__inner b-head-decor__inner_n3">
              <div class="b-ball b-ball_n1 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n2 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n3 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n4 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n5 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n6 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n7 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n8 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n9 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i1"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i2"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i3"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i4"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i5"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i6"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
            </i>
            <i class="b-head-decor__inner b-head-decor__inner_n4">
              <div class="b-ball b-ball_n1 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n2 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n3 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n4 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n5 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n6 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n7 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n8 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n9 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i1"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i2"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i3"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i4"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i5"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i6"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
            </i>
            <i class="b-head-decor__inner b-head-decor__inner_n5">
              <div class="b-ball b-ball_n1 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n2 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n3 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n4 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n5 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n6 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n7 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n8 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n9 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i1"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i2"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i3"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i4"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i5"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i6"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
            </i>
            <i class="b-head-decor__inner b-head-decor__inner_n6">
              <div class="b-ball b-ball_n1 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n2 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n3 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n4 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n5 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n6 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n7 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n8 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n9 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i1"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i2"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i3"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i4"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i5"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i6"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
            </i>
            <i class="b-head-decor__inner b-head-decor__inner_n7">
              <div class="b-ball b-ball_n1 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n2 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n3 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n4 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n5 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n6 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n7 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n8 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_n9 b-ball_bounce"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i1"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i2"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i3"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i4"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i5"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
              <div class="b-ball b-ball_i6"><div class="b-ball__right"></div><div class="b-ball__i"></div></div>
            </i>
        </i>
        </div>
    </div>

    <!-- Animated Snowflakes -->
    <div id="snowflakes" aria-hidden="true"></div>
    <script>
        const snowflakes = ['❄', '❅', '❆', '✻', '•'];
        const container = document.getElementById('snowflakes');
        for (let i = 0; i < 50; i++) {
            const flake = document.createElement('div');
            flake.className = 'snowflake';
            flake.style.left = Math.random() * 100 + 'vw';
            flake.style.animationDuration = (Math.random() * 8 + 6) + 's, ' + (Math.random() * 3 + 2) + 's';
            flake.style.animationDelay = Math.random() * 15 + 's, ' + Math.random() * 2 + 's';
            flake.style.fontSize = (Math.random() * 14 + 8) + 'px';
            flake.style.opacity = Math.random() * 0.6 + 0.2;
            flake.textContent = snowflakes[Math.floor(Math.random() * snowflakes.length)];
            container.appendChild(flake);
        }
    </script>

    <!-- Volume Indicator -->
    <div id="volumeIndicator">
        <div class="text-center text-santa-gold font-bold text-lg mb-2">
            <span id="volumePercent">60</span>%
        </div>
        <div class="volume-bar-container">
            <div id="volumeBarFill" class="volume-bar-fill" style="width: 60%;"></div>
        </div>
    </div>

    <div class="app-wrapper relative z-10 w-full max-w-4xl mx-auto px-2 sm:px-4">
        <header class="text-center mb-6 md:mb-10">
            <div class="font-santa text-4xl sm:text-5xl md:text-7xl text-santa-gold text-glow-gold drop-shadow-lg flex items-center justify-center gap-3 sm:gap-4">
                <a href="{{ route('home') }}">{{ config('app.name', 'Secret Santa') }}</a>
                <button id="musicToggle" class="hover:scale-110 transition-transform cursor-pointer select-none" title="Scroll to adjust volume">
                    <img id="speakerIcon" src="{{ asset('images/secret-santa.png') }}" alt="Music" class="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 inline-block">
                </button>
            </div>
            <p class="text-santa-mist text-sm sm:text-base md:text-lg mt-2 opacity-90 px-4">{{ __('app.header_subtitle') }}</p>
        </header>

        <main class="snow-card p-6 md:p-10 shadow-2xl relative overflow-visible">
            
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-santa-red p-4 rounded text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="mt-12 text-center text-santa-mist text-sm" x-data="{ supportModal: false }">
            <p class="opacity-60">&copy; {{ date('Y') }} Secret Santa. {{ __('app.footer_made_with') }}</p>
            <div class="mt-2 text-xs flex justify-center gap-4 items-center opacity-60">
                @if(auth()->check() && auth()->user()->is_admin)
                    <a href="{{ route('admin.index') }}" class="hover:underline hover:text-santa-gold transition-colors">Admin</a>
                    <span class="opacity-50">|</span>
                @endif

                <div class="flex gap-3">
                    <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-santa-gold font-bold' : 'hover:text-santa-gold transition-colors' }}">EN</a>
                    <span class="opacity-50">/</span>
                    <a href="{{ route('locale.switch', 'uk') }}" class="{{ app()->getLocale() === 'uk' ? 'text-santa-gold font-bold' : 'hover:text-santa-gold transition-colors' }}">UA</a>
                </div>

                <span class="opacity-50">|</span>

                <button @click="supportModal = true" class="hover:text-santa-gold transition-colors flex items-center gap-1">
                    <span>🫶</span>
                    <span>{{ __('app.support') }}</span>
                </button>
            </div>

            <!-- Support Modal -->
            <div x-show="supportModal"
                 x-cloak
                 @click.self="supportModal = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
                 style="display: none;">
                <div @click.away="supportModal = false"
                     class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center relative opacity-100">
                    <!-- Close button -->
                    <button @click="supportModal = false"
                            class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl font-bold">
                        &times;
                    </button>

                    <!-- Icon -->
                    <div class="text-6xl mb-4">🫶</div>

                    <!-- Title -->
                    <h2 class="font-santa text-3xl mb-4 text-santa-dark">{{ __('app.support_title') }}</h2>

                    <!-- Description -->
                    <p class="text-gray-800 text-sm leading-relaxed mb-6">
                        {{ __('app.support_description') }}
                    </p>

                    <!-- Donation Link -->
                    <a href="https://pay.oxapay.com/16295131"
                       target="_blank"
                       class="inline-block w-full bg-gradient-to-r from-santa-red to-santa-green text-white py-4 px-6 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                        💝 {{ __('app.support_button') }}
                    </a>

                    <p class="text-xs text-gray-400 mt-4">
                        {{ __('app.support_thanks') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Garland Bounce Script -->
    <script>
        function ballBounce(e) {
            if (e.className.indexOf(" bounce") > -1) return;
            toggleBounce(e);
        }
        function toggleBounce(i) {
            i.classList.add("bounce");
            setTimeout(() => {
                i.classList.remove("bounce");
                i.classList.add("bounce1");
                setTimeout(() => {
                    i.classList.remove("bounce1");
                    i.classList.add("bounce2");
                    setTimeout(() => {
                        i.classList.remove("bounce2");
                        i.classList.add("bounce3");
                        setTimeout(() => {
                            i.classList.remove("bounce3");
                        }, 300);
                    }, 300);
                }, 300);
            }, 300);
        }
        document.querySelectorAll('.b-ball_bounce').forEach(ball => {
            ball.addEventListener('mouseenter', function() { ballBounce(this); });
        });
        document.querySelectorAll('.b-ball_bounce .b-ball__right').forEach(right => {
            right.addEventListener('mouseenter', function() { ballBounce(this.parentElement); });
        });
    </script>

    <!-- Audio Player Control Script using Howler.js -->
    <script>
        let sound;
        let isPlaying = false;
        const MUSIC_STATE_KEY = 'christmas_music_playing';
        const MUSIC_TIME_KEY = 'christmas_music_time';
        const MUSIC_VOLUME_KEY = 'christmas_music_volume';

        // Volume constraints (behind the scenes)
        const MIN_VOLUME = 0;    // 0% (but display as 0-100%)
        const MAX_VOLUME = 0.5;  // 50% (but display as 0-100%)
        const DEFAULT_VOLUME = 0.3; // 30%

        let volumeIndicatorTimeout;

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎵 Initializing Howler.js audio player...');

            // Check saved state
            const savedState = localStorage.getItem(MUSIC_STATE_KEY);
            const savedTime = parseFloat(localStorage.getItem(MUSIC_TIME_KEY)) || 0;
            const savedVolume = parseFloat(localStorage.getItem(MUSIC_VOLUME_KEY)) || DEFAULT_VOLUME;

            console.log('📍 Saved state:', savedState, 'time:', savedTime, 'volume:', savedVolume);

            // Initialize Howler (using Web Audio API by default, NOT html5)
            sound = new Howl({
                src: ['{{ asset('music/christmas.mp3') }}'],
                loop: true,
                volume: savedVolume,
                preload: true,
                onload: function() {
                    console.log('✅ Audio loaded, duration:', sound.duration());

                    // Auto-play if was playing before (seek will happen in playMusic)
                    if (savedState === 'true') {
                        console.log('▶️ Auto-playing from saved state...');
                        // Small delay to ensure audio is fully ready
                        setTimeout(function() {
                            playMusic();
                        }, 100);
                    }
                },
                onplay: function() {
                    isPlaying = true;
                    updateIcon();
                    localStorage.setItem(MUSIC_STATE_KEY, 'true');
                    const currentPos = sound.seek();
                    console.log('🔊 Playback started at:', typeof currentPos === 'number' ? currentPos.toFixed(2) : currentPos);
                },
                onseek: function() {
                    const currentPos = sound.seek();
                    console.log('↪️ Seek completed to:', typeof currentPos === 'number' ? currentPos.toFixed(2) : currentPos);
                },
                onpause: function() {
                    isPlaying = false;
                    updateIcon();
                    localStorage.setItem(MUSIC_STATE_KEY, 'false');
                    console.log('⏸️ Playback paused at:', sound.seek());
                },
                onend: function() {
                    console.log('🔄 Track ended (should loop)');
                },
                onloaderror: function(id, error) {
                    console.error('❌ Load error:', error);
                },
                onplayerror: function(id, error) {
                    console.error('❌ Play error:', error);
                    // Try to unlock audio on next user interaction
                    sound.once('unlock', function() {
                        console.log('🔓 Audio unlocked, retrying...');
                        playMusic();
                    });
                }
            });

            // Setup toggle button
            const toggleBtn = document.getElementById('musicToggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    console.log('🎵 Music toggle clicked');
                    e.preventDefault();
                    toggleMusic();
                });

                // Volume control with scroll wheel (smoother adjustments)
                toggleBtn.addEventListener('wheel', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!sound) return;

                    // Get current volume
                    let currentVolume = sound.volume();

                    // Change by 2% (0.01) per scroll tick for smoother control
                    const delta = e.deltaY < 0 ? 0.01 : -0.01;
                    let newVolume = currentVolume + delta;

                    // Clamp between MIN and MAX
                    newVolume = Math.max(MIN_VOLUME, Math.min(MAX_VOLUME, newVolume));

                    // Set new volume
                    sound.volume(newVolume);
                    localStorage.setItem(MUSIC_VOLUME_KEY, newVolume.toString());

                    console.log('🔊 Volume:', (newVolume * 200).toFixed(0) + '%');

                    // Show volume indicator
                    showVolumeIndicator(newVolume);
                }, { passive: false });

                // Double-click to restart from beginning (MacBook trackpad friendly!)
                toggleBtn.addEventListener('dblclick', function(e) {
                    e.preventDefault();
                    console.log('👆 Double-click detected');
                    restartMusic();
                });

                console.log('✅ Toggle button ready with volume control');
            }

            // Update initial volume indicator
            updateVolumeIndicator(savedVolume);

            // Save position periodically
            setInterval(function() {
                if (sound && isPlaying) {
                    const currentTime = sound.seek();
                    if (typeof currentTime === 'number' && currentTime > 0) {
                        localStorage.setItem(MUSIC_TIME_KEY, currentTime.toString());
                        console.log('💾 Saved time:', currentTime.toFixed(1) + 's');
                    }
                }
            }, 3000);

            // Save on page unload
            window.addEventListener('beforeunload', function() {
                if (sound && isPlaying) {
                    const currentTime = sound.seek();
                    if (typeof currentTime === 'number') {
                        localStorage.setItem(MUSIC_TIME_KEY, currentTime.toString());
                        console.log('💾 [beforeunload] Saved time:', currentTime);
                    }
                }
            });

            // Update initial icon
            updateIcon();
        });

        function playMusic() {
            if (!sound) {
                console.error('❌ Sound not initialized');
                return;
            }

            const savedTime = parseFloat(localStorage.getItem(MUSIC_TIME_KEY)) || 0;

            // If we have a saved time, seek first, THEN play
            if (savedTime > 0 && savedTime < sound.duration()) {
                console.log('🎯 Seeking to:', savedTime, 'before playing');

                // Seek and wait for it to complete
                sound.seek(savedTime);

                // Play after a tiny delay to let seek complete
                setTimeout(function() {
                    console.log('▶️ Playing after seek, position:', sound.seek());
                    sound.play();
                }, 50);
            } else {
                console.log('▶️ Playing from start');
                sound.play();
            }
        }

        function pauseMusic() {
            if (!sound) {
                console.error('❌ Sound not initialized');
                return;
            }

            // Get current position BEFORE pausing
            const currentTime = sound.seek();

            // Save position immediately so it continues from here
            if (typeof currentTime === 'number' && currentTime > 0) {
                localStorage.setItem(MUSIC_TIME_KEY, currentTime.toString());
                console.log('⏸️ Pausing at:', currentTime, '- position saved');
            }

            sound.pause();
        }

        function toggleMusic() {
            console.log('🔄 Toggle, current state:', isPlaying);
            if (isPlaying) {
                pauseMusic();
            } else {
                playMusic();
            }
        }

        function restartMusic() {
            if (!sound) {
                console.error('❌ Sound not initialized');
                return;
            }

            console.log('🔄 Restarting from beginning...');

            // Set position to 0
            sound.seek(0);
            localStorage.setItem(MUSIC_TIME_KEY, '0');

            // If music is playing, it will continue from start
            // If paused, it will just reset position
            if (!isPlaying) {
                console.log('📍 Position reset to start (paused)');
            } else {
                console.log('▶️ Playing from start');
            }
        }

        function updateIcon() {
            const icon = document.getElementById('speakerIcon');
            if (icon) {
                // Switch between Angel (playing) and Secret Santa (paused) images
                if (isPlaying) {
                    icon.src = '{{ asset('images/angel.png') }}';
                    icon.alt = 'Music Playing';
                    icon.classList.add('angel-floating');
                } else {
                    icon.src = '{{ asset('images/secret-santa.png') }}';
                    icon.alt = 'Music Paused';
                    icon.classList.remove('angel-floating');
                }
                console.log('🎨 Icon:', isPlaying ? 'angel (playing)' : 'secret-santa (paused)');
            }

            // Add visual indicator if autoplay was blocked
            const toggleBtn = document.getElementById('musicToggle');
            if (toggleBtn && !isPlaying && localStorage.getItem(MUSIC_STATE_KEY) === 'true') {
                toggleBtn.style.animation = 'pulse 1s infinite';
                toggleBtn.title = 'Click to resume | Scroll for volume | Double-click to restart';
            } else if (toggleBtn) {
                toggleBtn.style.animation = '';
                toggleBtn.title = 'Click to toggle | Scroll for volume | Double-click to restart';
            }
        }

        function updateVolumeIndicator(volume) {
            // Display as 0-100% (multiply actual volume by 2, since max is 0.5)
            const displayPercent = Math.round(volume * 200); // 0.5 * 200 = 100%
            const percentEl = document.getElementById('volumePercent');
            const barFill = document.getElementById('volumeBarFill');

            if (percentEl) {
                percentEl.textContent = displayPercent;
            }

            if (barFill) {
                // Visual bar shows 0-100% range
                barFill.style.width = displayPercent + '%';
            }
        }

        function showVolumeIndicator(volume) {
            const indicator = document.getElementById('volumeIndicator');
            if (!indicator) return;

            // Update the indicator
            updateVolumeIndicator(volume);

            // Show the indicator
            indicator.classList.add('show');

            // Clear existing timeout
            if (volumeIndicatorTimeout) {
                clearTimeout(volumeIndicatorTimeout);
            }

            // Hide after 2 seconds
            volumeIndicatorTimeout = setTimeout(function() {
                indicator.classList.remove('show');
            }, 2000);
        }
    </script>
</body>
</html>
