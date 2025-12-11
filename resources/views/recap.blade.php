<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Chat Wrapped</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        /* PALETTE */
        :root {
            --dark-bg: #1B3C53;
            --card-bg: #234C6A;
            --accent: #456882;
            --text: #E3E3E3;
            --primary: #25D366; /* WA Green */
            --tele: #0088cc; /* Telegram Blue */
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        /* CARD STYLE */
        .upload-card {
            background-color: var(--card-bg);
            padding: 50px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            text-align: center;
            width: 100%;
            max-width: 450px;
            border: 1px solid var(--accent);
            transition: all 0.5s ease;
            position: relative;
            z-index: 2;
        }

        h1 { margin: 0; font-size: 2.2rem; letter-spacing: 1px; color: #fff; font-weight: 800; }
        p { color: #aebcc9; font-weight: 300; margin-bottom: 30px; font-size: 1rem; line-height: 1.5; }

        .form-group { margin-bottom: 25px; }

        /* INPUT FILE CANTIK */
        input[type="file"] {
            display: block; width: 100%; padding: 20px;
            background: rgba(0,0,0,0.2);
            border: 2px dashed var(--accent);
            border-radius: 15px;
            color: var(--text);
            box-sizing: border-box;
            cursor: pointer;
            transition: 0.3s;
            outline: none;
        }
        input[type="file"]:hover { border-color: #fff; background: rgba(255,255,255,0.05); }

        /* TOMBOL KEREN */
        button {
            background: linear-gradient(135deg, var(--primary), var(--tele));
            color: white; border: none; padding: 18px 30px;
            border-radius: 50px; font-size: 1.1rem; font-weight: 700;
            cursor: pointer; width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 20px rgba(0,0,0, 0.3);
        }
        button:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 30px rgba(0,0,0, 0.5); }

        .icon { font-size: 4rem; margin-bottom: 15px; display: block; animation: float 3s ease-in-out infinite; }

        /* FORMAT BADGES */
        .badges { display: flex; justify-content: center; gap: 10px; margin-bottom: 20px; }
        .badge { font-size: 0.75rem; padding: 5px 10px; border-radius: 20px; background: rgba(255,255,255,0.1); font-weight: 600; }
        .badge-wa { color: #25D366; border: 1px solid #25D366; }
        .badge-tg { color: #0088cc; border: 1px solid #0088cc; }

        /* LOADING SCREEN (Hidden by default) */
        #loading-screen {
            display: none;
            text-align: center;
            animation: fadeIn 0.5s;
        }
        
        .spinner {
            width: 60px; height: 60px;
            border: 6px solid rgba(255,255,255,0.1);
            border-top-color: var(--primary);
            border-right-color: var(--tele);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px auto;
        }

        .loading-text { font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 10px; }
        .loading-sub { color: #aebcc9; font-size: 1rem; }

        /* ANIMATIONS */
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        
        /* BACKGROUND PARTICLES */
        .bg-circle {
            position: absolute; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(0,0,0,0) 70%);
            z-index: 1; pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="bg-circle" style="width: 600px; height: 600px; top: -150px; left: -150px;"></div>
    <div class="bg-circle" style="width: 500px; height: 500px; bottom: -100px; right: -100px;"></div>

    <div class="upload-card" id="form-card">
        <span class="icon">📂</span>
        <h1>Chat Wrapped</h1>
        <p>Upload history chat kamu untuk melihat statistik & analisis AI.</p>

        <div class="badges">
            <span class="badge badge-wa">WhatsApp (.txt)</span>
            <span class="badge badge-tg">Telegram (.json)</span>
        </div>

        <form id="uploadForm" action="{{ route('recap.process') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            @if ($errors->any())
                <div style="background: rgba(255,0,0,0.2); color: #ffcccc; padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; border: 1px solid #ff4444;">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="form-group">
                <input type="file" name="file" required accept=".txt,.json">
            </div>
            <button type="submit">Mulai Analisis 🚀</button>
        </form>
    </div>

    <div id="loading-screen">
        <div class="spinner"></div>
        <div class="loading-text" id="loading-msg">Sedang Membaca File...</div>
        <div class="loading-sub">Mohon tunggu, jangan tutup halaman ini.</div>
    </div>

    <script>
        const form = document.getElementById('uploadForm');
        const formCard = document.getElementById('form-card');
        const loadingScreen = document.getElementById('loading-screen');
        const msgElement = document.getElementById('loading-msg');

        // Kata-kata loading lucu
        const messages = [
            "Mendeteksi platform chat...",
            "Menerjemahkan bahasa alien...",
            "Menghitung jumlah 'wkwk'...",
            "Menganalisis siapa yang paling toxic...",
            "Menyusun drama tahun ini...",
            "AI sedang bekerja keras..."
        ];

        form.addEventListener('submit', function() {
            // Sembunyikan form, munculkan loading
            formCard.style.display = 'none';
            loadingScreen.style.display = 'block';

            // Ganti teks setiap 2 detik
            let i = 0;
            setInterval(() => {
                msgElement.innerText = messages[i % messages.length];
                i++;
            }, 2000);
        });
    </script>

</body>
</html>