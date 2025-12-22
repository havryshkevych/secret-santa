<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Secret Santa') }}</title>
    <meta name="description" content="Організуй ідеальний обмін подарунками Secret Santa. Створюй групи, додавай обмеження та отримуй сповіщення в Telegram.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mountains+of+Christmas:wght@400;700&family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
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
                        display: ['"Mountains of Christmas"', 'cursive'],
                        body: ['"Outfit"', 'sans-serif'],
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
    </style>
</head>
<body class="font-body min-h-screen flex items-center justify-center p-4 pt-24">

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

    <div class="relative z-10 w-full max-w-4xl mx-auto">
        <header class="text-center mb-10">
            <div class="font-display text-5xl md:text-7xl text-santa-gold text-glow-gold drop-shadow-lg">
                <a href="{{ route('home') }}">{{ config('app.name', 'Secret Santa') }}</a>
            </div>
            <p class="text-santa-mist text-lg mt-2 opacity-90">Організуй святковий обмін подарунками — легко та весело! 🎅</p>
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
            <p>&copy; {{ date('Y') }} Secret Santa. Зроблено з ❤️</p>
            <div class="mt-2 text-xs flex justify-center gap-4">
                <span>Laravel & Docker</span>
                <a href="{{ route('admin.index') }}" class="hover:underline hover:text-santa-gold transition-colors">Адмінка</a>
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
