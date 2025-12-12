<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Wrapped</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('pets.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('pets.png') }}">
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

        * { box-sizing: border-box; }

        body {
            background-color: var(--dark-bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; 
            margin: 0;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }

        /* CONTAINER UTAMA (PENGATUR LAYOUT) */
        .main-container {
            display: flex;
            align-items: center; /* Vertikal center */
            justify-content: center; /* Horizontal center */
            gap: 60px; /* Jarak antara teks kiri dan kartu kanan */
            width: 100%;
            max-width: 1100px;
            z-index: 2;
        }

        /* BAGIAN KIRI (TEKS & INFO) */
        .left-content {
            flex: 1; /* Ambil sisa ruang */
            max-width: 500px;
            text-align: left;
        }

        h1 { margin: 0 0 10px 0; font-size: 3rem; letter-spacing: 1px; color: #fff; font-weight: 800; line-height: 1.1; }
        p.subtitle { color: #aebcc9; font-weight: 300; margin-bottom: 30px; font-size: 1.2rem; line-height: 1.5; }

        /* BAGIAN KANAN (CARD FORM) */
        .right-content {
            flex: 0 0 400px; /* Lebar fix untuk card */
            width: 100%;
        }

        .upload-card {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            text-align: center;
            border: 1px solid var(--accent);
            transition: all 0.5s ease;
        }

        /* INFO BOX STYLING */
        .info-box {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            color: #d1d5db;
            line-height: 1.5;
        }
        
        .info-item:last-child { margin-bottom: 0; }
        
        .info-icon { 
            font-size: 1.4rem; 
            min-width: 25px;
            background: rgba(255,255,255,0.1);
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px;
        }
        
        .info-title {
            color: #fff;
            font-weight: 700;
            display: block;
            margin-bottom: 4px;
            font-size: 1rem;
        }

        /* FORM ELEMENTS */
        .form-group { margin-bottom: 25px; }

        input[type="file"] {
            display: block; width: 100%; padding: 20px;
            background: rgba(0,0,0,0.2);
            border: 2px dashed var(--accent);
            border-radius: 15px;
            color: var(--text);
            cursor: pointer;
            transition: 0.3s;
            outline: none;
        }
        input[type="file"]:hover { border-color: #fff; background: rgba(255,255,255,0.05); }

        button {
            background: linear-gradient(135deg, var(--primary), var(--tele));
            color: white; border: none; padding: 18px 30px;
            border-radius: 50px; font-size: 1.1rem; font-weight: 700;
            cursor: pointer; width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 20px rgba(0,0,0, 0.3);
        }
        button:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 30px rgba(0,0,0, 0.5); }

        .icon-hero { font-size: 4rem; display: block; margin-bottom: 10px; animation: float 3s ease-in-out infinite; }

        .badges { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; }
        .badge { font-size: 0.8rem; padding: 6px 12px; border-radius: 20px; background: rgba(255,255,255,0.1); font-weight: 600; }
        .badge-wa { color: #25D366; border: 1px solid #25D366; }

        /* LOADING */
        #loading-screen {
            display: none;
            text-align: center;
            animation: fadeIn 0.5s;
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
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

        /* ANIMATIONS & BG */
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .bg-circle {
            position: absolute; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(0,0,0,0) 70%);
            z-index: 1; pointer-events: none;
        }

        /* --- MOBILE RESPONSIVE (ANDROID STYLE) --- */
        @media (max-width: 900px) {
            /* Ubah layout jadi 1 kolom */
            .main-container {
                flex-direction: column; 
                gap: 30px;
                max-width: 500px; /* Batasi lebar biar rapi di tablet/HP */
            }

            /* Teks Kiri jadi rata tengah */
            .left-content {
                text-align: center;
                width: 100%;
            }

            .right-content {
                flex: auto;
                width: 100%;
            }

            h1 { font-size: 2.2rem; }
            .icon-hero { font-size: 3.5rem; margin: 0 auto 10px auto; }

            /* Sembunyikan Info Box di Mobile jika ingin tampilan simple ala 'Android' 
               Atau biarkan muncul di bawah judul. Di sini saya biarkan muncul. */
            .info-box {
                text-align: left; /* Teks dalam box tetap rata kiri biar enak dibaca */
                padding: 20px;
            }
            
            /* Background circle hilang di mobile */
            .bg-circle { display: none; }
        }
    </style>
</head>
<body>

    <div class="bg-circle" style="width: 700px; height: 700px; top: -200px; left: -200px;"></div>
    <div class="bg-circle" style="width: 500px; height: 500px; bottom: -100px; right: -100px;"></div>

    <div class="main-container" id="main-content">
        
        <div class="left-content">
            <span class="icon-hero">📂</span>
            <h1>Chat Wrapped</h1>
            <p class="subtitle">Lihat kepribadian chat, jam tersibuk, hingga siapa yang paling "toxic" di grup kalian!</p>

            <div class="info-box">
                <div class="info-item">
                    <div class="info-icon">🔒</div>
                    <div>
                        <span class="info-title">Privasi Dijaga</span>
                        File chat hanya diproses sementara dan <strong>tidak disimpan</strong> di server.
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">📊</div>
                    <div>
                        <span class="info-title">Analisis Lengkap</span>
                        Statistik detail: Top words, emoji favorit, reply speed, hingga ghosting counter.
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">🤖</div>
                    <div>
                        <span class="info-title">AI Powered</span>
                        Dapatkan kesimpulan unik dan "roasting" pedas dari AI tentang gaya chat kalian.
                    </div>
                </div>
            </div>
        </div>

        <div class="right-content">
            <div class="upload-card">
                <div class="badges">
                    <span class="badge badge-wa">Support WhatsApp .txt</span>
                </div>

                <form id="uploadForm" action="{{ route('recap.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    @if ($errors->any())
                        <div style="background: rgba(255,0,0,0.2); color: #ffcccc; padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; border: 1px solid #ff4444;">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div style="background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                            <strong>GAGAL!</strong> {{ session('error') }}
                        </div>
                    @endif

                    <div class="form-group">
                        <input type="file" name="file" required accept=".txt,.json">
                    </div>
                    <button type="submit">Mulai Analisis!</button>
                </form>
            </div>
        </div>

    </div>

    <div id="loading-screen">
        <div class="spinner"></div>
        <div class="loading-text" id="loading-msg">Sedang Membaca File...</div>
        <div class="loading-sub">Mohon tunggu, jangan tutup halaman ini.</div>
    </div>

    <script>
        const form = document.getElementById('uploadForm');
        const mainContent = document.getElementById('main-content');
        const loadingScreen = document.getElementById('loading-screen');
        const msgElement = document.getElementById('loading-msg');

        const messages = [
            "Mendeteksi platform chat...",
            "Menerjemahkan bahasa alien...",
            "Menghitung jumlah 'wkwk'...",
            "Menganalisis tingkat ke-bucin-an...",
            "Menyusun drama tahun ini...",
            "AI sedang bekerja keras..."
        ];

        form.addEventListener('submit', function() {
            mainContent.style.display = 'none'; // Hilangkan seluruh konten utama
            loadingScreen.style.display = 'block'; // Munculkan loading di tengah layar

            let i = 0;
            setInterval(() => {
                msgElement.innerText = messages[i % messages.length];
                i++;
            }, 2000);
        });
    </script>

</body>
</html>