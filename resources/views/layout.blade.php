<!-- Stray CSS removed. All styles are now inside the <style> block below. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'chatty_cat')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Modern filled button */
        .btn-gradient {
            background: linear-gradient(90deg, #7f53ac 0%, #5e3a8c 100%);
            color: #fff;
            font-weight: 700;
            letter-spacing: .3px;
            border: none;
            border-radius: 1.2rem;
            box-shadow: 0 4px 16px -4px #7f53ac55;
            position: relative;
            overflow: hidden;
            font-size: 1.05rem;
            padding: .65rem 1.5rem;
            transition: all .18s cubic-bezier(.4,0,.2,1);
        }
        .btn-gradient:before {
            display: none;
        }
        .btn-gradient:hover, .btn-gradient:focus {
            background: linear-gradient(90deg, #5e3a8c 0%, #7f53ac 100%);
            color: #fff;
            box-shadow: 0 6px 24px -6px #7f53ac88;
            transform: translateY(-1px) scale(1.04);
            text-decoration: none;
        }
        .btn-gradient:active {
            background: linear-gradient(90deg, #4a2e6b 0%, #7f53ac 100%);
            transform: scale(0.98);
            box-shadow: 0 2px 8px -2px #7f53ac33;
        }

        /* Modern purple outline for secondary actions */
        .btn-outline-accent {
            color: #7f53ac;
            border: 2px solid #7f53ac;
            background: transparent;
            font-weight: 700;
            border-radius: 1.2rem;
            padding: 0.55rem 1.4rem;
            box-shadow: 0 2px 10px 0 #7f53ac18;
            letter-spacing: 0.5px;
            font-size: 1.05rem;
            transition: all .18s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
        }
        .btn-outline-accent:hover, .btn-outline-accent:focus {
            background: linear-gradient(90deg, #f6f3fa 60%, #e0e7ff 100%);
            color: #5e3a8c;
            border-color: #5e3a8c;
            box-shadow: 0 4px 16px -4px #7f53ac33, 0 1.5px 8px 0 #fbc2eb11;
            transform: translateY(-1px) scale(1.04);
            text-decoration: none;
        }
        :root {
            --cc-bg: linear-gradient(135deg,#e0e7ff 0%,#fbc2eb 100%);
            --cc-surface: rgba(255,255,255,0.18);
            --cc-surface-blur: blur(18px) saturate(180%);
            --cc-accent: #7f53ac;
            --cc-accent-alt: #fbc2eb;
            --cc-accent-grad: linear-gradient(90deg,#7f53ac,#fbc2eb);
            --cc-text: #232946;
        }
        html, body { height: 100%; }
        body { background:var(--cc-bg); color:var(--cc-text); font-family:'Nunito',sans-serif; min-height:100vh; min-height:100dvh; display:flex; flex-direction:column; }
        .navbar { background:rgba(255,255,255,0.12)!important; backdrop-filter:blur(12px); border-bottom:1px solid rgba(127,83,172,0.08); box-shadow:0 2px 12px -6px #7f53ac22; }
        .navbar-brand { font-weight:700; letter-spacing:.5px; color:var(--cc-accent)!important; }
        .chat-badge { background:var(--cc-accent-grad); color:#fff; border-radius:14px; padding:3px 10px; font-size:.65rem; margin-left:.45rem; font-weight:700; box-shadow:0 2px 6px -2px #fbc2eb55; }
        a { color:var(--cc-accent); transition:.25s; }
        a:hover { color:#232946; }
        main.container { animation:fadeSlide .6s cubic-bezier(.4,0,.2,1); flex:1 0 auto; }
        @keyframes fadeSlide { from{opacity:0; transform:translateY(24px);} to{opacity:1; transform:translateY(0);} }
        .card { background:var(--cc-surface); border-radius:2rem; box-shadow:0 8px 32px 0 #7f53ac22,0 1.5px 8px 0 #fbc2eb22; border:1px solid #fff2; backdrop-filter:var(--cc-surface-blur); overflow:hidden; }
        .form-control { background:rgba(255,255,255,.22); border:1.5px solid #e0e7ff; color:var(--cc-text); border-radius:1rem; transition:.3s; font-size:1.1rem; }
        .form-control:focus { background:rgba(255,255,255,.32); border-color:var(--cc-accent); box-shadow:0 0 0 4px #fbc2eb55; color:var(--cc-text); }
        .form-label { font-weight:600; letter-spacing:.3px; color:var(--cc-accent); }
            .btn-outline-purple {
                color: #7f53ac;
                border: 2.5px solid #7f53ac;
                background: transparent;
                font-weight: 700;
                border-radius: 1.5rem;
                padding: 0.55rem 1.4rem;
                box-shadow: 0 2px 10px 0 #7f53ac18;
                letter-spacing: 0.5px;
                font-size: 1.05rem;
                transition: all .18s cubic-bezier(.4,0,.2,1);
                position: relative;
                overflow: hidden;
            }
            .btn-outline-purple:hover, .btn-outline-purple:focus {
                background: linear-gradient(90deg, #f6f3fa 60%, #e0e7ff 100%);
                color: #5e3a8c;
                border-color: #5e3a8c;
                box-shadow: 0 4px 16px -4px #7f53ac33, 0 1.5px 8px 0 #fbc2eb11;
                transform: translateY(-1px) scale(1.04);
                text-decoration: none;
            }
        .tiny-hint { font-size:.7rem; text-transform:uppercase; letter-spacing:1px; opacity:.65; }
        .floating-cats { position:fixed; inset:0; pointer-events:none; overflow:hidden; z-index:0; }
        .cat-paw { position:absolute; font-size:42px; opacity:.04; animation:floaty 14s linear infinite; }
        @keyframes floaty { 0%{ transform:translateY(120vh) rotate(0deg);} 100%{ transform:translateY(-20vh) rotate(360deg);} }
        footer { flex-shrink:0; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<div class="floating-cats">
    <span class="cat-paw" style="left:8%; animation-delay:-2s">🐾</span>
    <span class="cat-paw" style="left:38%; animation-delay:-8s">🐾</span>
    <span class="cat-paw" style="left:68%; animation-delay:-4s">🐾</span>
    <span class="cat-paw" style="left:88%; animation-delay:-10s">🐾</span>
</div>
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="{{ route('posts.index') }}">
            <i class="fas fa-cat brand-icon"></i>
            chatty_cat <span class="chat-badge">beta</span>
        </a>
    </div>
</nav>
<main class="container mt-5">@yield('content')</main>
<footer class="text-dark py-2 small" style="background:rgba(255,255,255,0.18);backdrop-filter:blur(8px);border-top:1px solid #e0e7ff33;"><div class="container text-center small"><span>&copy; 2025 chatty_cat • crafted with <i class="fas fa-heart text-danger"></i></span></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
