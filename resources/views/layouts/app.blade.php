<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Secret Santa') }}</title>
    <meta name="description" content="{{ __('app.meta_description') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Marck+Script&family=Mountains+of+Christmas:wght@400;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        // Telegram Web App Auto-Login
        document.addEventListener('DOMContentLoaded', function() {
                if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initData) {
                window.Telegram.WebApp.expand();
                const initData = window.Telegram.WebApp.initData;
                
                // Only try to login if not already logged in
                @guest
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
                            window.location.reload();
                        }
                    })
                    .catch(error => console.error('Telegram WebApp login error:', error));
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
        
        /* Gold text glow */
        .text-glow-gold {
            text-shadow: 0 0 30px rgba(240, 194, 88, 0.5), 0 2px 4px rgba(0,0,0,0.3);
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
    </style>
</head>
<body class="font-body min-h-screen flex items-center justify-center p-4 pt-32">

    @if(!session('locale') && (!auth()->check() || !auth()->user()->language))
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" id="language-modal">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center relative overflow-hidden">
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

    <div class="app-wrapper relative z-10 w-full max-w-4xl mx-auto px-2 sm:px-4">
        <header class="text-center mb-6 md:mb-10">
            <div class="font-santa text-4xl sm:text-5xl md:text-7xl text-santa-gold text-glow-gold drop-shadow-lg">
                <a href="{{ route('home') }}">{{ config('app.name', 'Secret Santa') }}</a>
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

        <footer class="mt-12 text-center text-santa-mist text-sm opacity-60">
            <p>&copy; {{ date('Y') }} Secret Santa. {{ __('app.footer_made_with') }}</p>
            <div class="mt-2 text-xs flex justify-center gap-4 items-center">
                @if(auth()->check() && auth()->user()->is_admin)
                    <a href="{{ route('admin.index') }}" class="hover:underline hover:text-santa-gold transition-colors">Admin</a>
                    <span class="opacity-50">|</span>
                @endif
                
                <div class="flex gap-3">
                    <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-santa-gold font-bold' : 'hover:text-santa-gold transition-colors' }}">EN</a>
                    <span class="opacity-50">/</span>
                    <a href="{{ route('locale.switch', 'uk') }}" class="{{ app()->getLocale() === 'uk' ? 'text-santa-gold font-bold' : 'hover:text-santa-gold transition-colors' }}">UA</a>
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
</body>
</html>
