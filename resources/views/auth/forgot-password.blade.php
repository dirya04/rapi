<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - RAPI-Sekolah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --board: #123c33;
            --board-2: #1b5246;
            --paper: #f4f7f5;
            --card: #ffffff;
            --ink: #14231e;
            --ink-muted: #5d6f68;
            --line: #dce5e0;
            --yellow: #f6b800;
            --yellow-dark: #c99500;
            --focus: #1d7a63;
            --body: 'Public Sans', system-ui, sans-serif;
            --display: 'Bricolage Grotesque', system-ui, sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--body);
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
        }
        .box {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(18, 60, 51, 0.06);
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
        }
        .brand {
            font-family: var(--display);
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--board);
            margin-bottom: 6px;
        }
        .brand span { color: var(--yellow); }
        .desc {
            font-size: 0.88rem;
            color: var(--ink-muted);
            margin-bottom: 24px;
            line-height: 1.45;
        }
        .field { margin-bottom: 20px; }
        label { display: block; font-size: 0.86rem; font-weight: 600; margin-bottom: 8px; }
        input {
            width: 100%; padding: 12px 14px; font: 400 .94rem var(--body);
            border: 1.5px solid var(--line); border-radius: 10px; background: #fafcfa;
            transition: all 0.2s;
        }
        input:focus { outline: none; border-color: var(--focus); box-shadow: 0 0 0 3px rgba(29,122,99,.15); background: #fff; }
        .btn {
            width: 100%; border: 0; cursor: pointer; padding: 13px; border-radius: 10px;
            font: 700 .95rem var(--body); background: var(--board); color: #fff;
            transition: background .2s, transform .1s;
        }
        .btn:hover { background: var(--board-2); }
        .btn:active { transform: translateY(1px); }
        .alert {
            padding: 12px 14px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 20px; font-weight: 500;
        }
        .alert-danger { background: #fdf2f2; color: #9b1c1c; border: 1px solid #fbd5d5; }
        .alert-success { background: #def7ec; color: #03543f; border: 1px solid #bcf0da; }
        .links {
            margin-top: 24px; text-align: center; font-size: 0.85rem;
        }
        .links a { color: var(--focus); text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; }
        .badge-wa {
            display: inline-flex; align-items: center; gap: 6px; background: #eaf8f1;
            color: #127c4d; font-size: 0.76rem; font-weight: 700; padding: 4px 10px;
            border-radius: 99px; margin-bottom: 14px;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="badge-wa">💬 WhatsApp OTP Security</div>
        <div class="brand">RAPI<span>-</span>Sekolah</div>
        <p class="desc">Masukkan username atau nomor WhatsApp terdaftar. Kami akan mengirimkan 6-digit kode OTP untuk mereset kata sandi Anda.</p>

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('password.forgot.send') }}" method="POST">
            @csrf
            <div class="field">
                <label for="identifier">Username atau Nomor WhatsApp</label>
                <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus placeholder="Contoh: guru_budi atau 081234567890">
            </div>
            <button type="submit" class="btn">Kirim Kode OTP WhatsApp</button>
        </form>

        <div class="links">
            <a href="{{ route('login') }}">← Kembali ke Halaman Login</a>
        </div>
    </div>
</body>
</html>
