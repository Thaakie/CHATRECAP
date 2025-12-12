<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Wrapped Ultimate</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        /* COLOR PALETTE (Dark Blue Theme) */
        :root {
            --c-bg: #1B3C53;       
            --c-card: #234C6A;     
            --c-accent: #456882;   
            --c-text: #E3E3E3;     
            --c-highlight: #ffffff;
            --c-heat-0: #2c5270; --c-heat-1: #4a8ab0; --c-heat-2: #74c0fc; --c-heat-3: #ffffff;
        }

        body { background-color: var(--c-bg); color: var(--c-text); font-family: 'Outfit', sans-serif; margin: 0; padding: 40px 20px; overflow-x: hidden; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid var(--c-accent); padding-bottom: 20px; }
        .header h1 { font-size: 3.5rem; font-weight: 800; margin: 0; letter-spacing: 2px; text-transform: uppercase; animation: fadeInDown 0.8s ease-out; }
        .header p { color: #aebcc9; margin-top: 5px; font-size: 1.1rem; animation: fadeInDown 1s ease-out; }

        /* USER SELECTOR */
        .user-selector-container { margin-top: 20px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .btn-select {
            background: rgba(0,0,0,0.3); color: #fff; border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 16px; border-radius: 20px; cursor: pointer; font-family: 'Outfit', sans-serif;
            transition: 0.3s;
        }
        .btn-select:hover { background: rgba(255,255,255,0.1); }
        .btn-select.active { background: #fff; color: #1B3C53; font-weight: bold; border-color: #fff; }

        #capture-area { background-color: var(--c-bg); padding: 30px; border-radius: 20px; }

        /* GRID SYSTEM */
        .grid-wrapper { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; grid-auto-rows: minmax(100px, auto); }
        .card { background-color: var(--c-card); border: 1px solid var(--c-accent); border-radius: 20px; padding: 25px; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s ease; position: relative; overflow: hidden; }
        .card:hover { transform: translateY(-3px); border-color: var(--c-text); }

        .span-1 { grid-column: span 1; } .span-2 { grid-column: span 2; } .span-3 { grid-column: span 3; } .span-4 { grid-column: span 4; } .row-2 { grid-row: span 2; } 
        @media (max-width: 900px) { .grid-wrapper { grid-template-columns: repeat(2, 1fr); } .span-1, .span-2, .span-3, .span-4 { grid-column: span 2; } .row-2 { grid-row: auto; } }

        .label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px; color: #aebcc9; margin-bottom: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .value { font-size: 2rem; font-weight: 800; color: var(--c-highlight); line-height: 1.1; word-break: break-word; }
        .sub-value { font-size: 1rem; color: var(--c-text); opacity: 0.9; margin-top: 5px; font-weight: 600; }
        .desc { font-size: 0.85rem; opacity: 0.8; margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; line-height: 1.5; font-weight: 300; }

        .card-highlight { background: linear-gradient(135deg, #FF5722, #BF360C); border: none; }
        .card-ai { background: linear-gradient(135deg, #234C6A, #163042); border: 1px solid #fff; }
        .ai-text { font-size: 1.2rem; line-height: 1.6; font-style: italic; }

        .user-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 10px; background: rgba(0,0,0,0.1); margin-bottom: 8px; border-radius: 10px; border-left: 4px solid transparent; }
        .user-row.top-1 { border-left-color: #FFD700; background: linear-gradient(90deg, rgba(255,215,0,0.1), transparent); }
        .user-row.top-2 { border-left-color: #C0C0C0; }
        .user-row.top-3 { border-left-color: #CD7F32; }
        .rank-num { font-size: 1.2rem; font-weight: 800; width: 30px; color: rgba(255,255,255,0.5); }
        .user-info { display: flex; flex-direction: column; flex: 1; }
        .user-name { font-weight: 700; font-size: 1rem; color: #fff; }
        .user-badges { display: flex; gap: 5px; margin-top: 4px; flex-wrap: wrap; }
        .badge-pill { font-size: 0.65rem; background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 10px; }
        .msg-count { font-weight: 800; font-size: 1.1rem; color: var(--c-highlight); }
        .user-words { font-size: 0.7rem; color: #aebcc9; margin-top: 2px; font-style: italic; }

        .moral-bar-container { display: flex; height: 25px; border-radius: 50px; overflow: hidden; margin: 20px 0; background: #000; width: 100%; box-shadow: inset 0 2px 5px rgba(0,0,0,0.5); }
        .bar-holy { background: #4ade80; height: 100%; display: flex; align-items: center; padding-left: 10px; font-size: 0.7rem; color: #000; font-weight: bold;}
        .bar-toxic { background: #f87171; height: 100%; display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; font-size: 0.7rem; color: #000; font-weight: bold;}

        .media-box { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center; margin-top: auto; }
        .media-item { background: rgba(0,0,0,0.25); padding: 12px 5px; border-radius: 12px; transition: transform 0.2s;}
        .media-item:hover { transform: scale(1.05); background: rgba(255,255,255,0.1); }
        .media-icon { font-size: 1.8rem; display: block; margin-bottom: 5px; }
        .media-count { font-weight: 800; font-size: 1.1rem; color: #fff; }
        .media-name { font-size: 0.6rem; text-transform: uppercase; letter-spacing: 1px; color: #aebcc9; margin-top: 2px;}

        .quote-card { background-color: var(--c-card); border: 1px solid #FFD700; position: relative; }
        .quote-icon { position: absolute; top: 10px; left: 20px; font-size: 4rem; opacity: 0.1; font-family: serif; color: #FFD700; }
        .quote-text { font-family: 'Fredoka', sans-serif; font-size: 1.6rem; font-weight: 500; line-height: 1.5; color: #fff; margin: 20px 0; white-space: pre-wrap; }
        .quote-author { text-align: right; font-size: 1rem; color: #FFD700; font-weight: bold; font-family: 'Outfit', sans-serif;}

        .heatmap-scroll { overflow-x: auto; white-space: nowrap; padding-bottom: 10px; }
        .heatmap-grid { display: inline-grid; grid-template-rows: repeat(7, 10px); grid-auto-flow: column; gap: 4px; }
        .heat-box { width: 10px; height: 10px; border-radius: 2px; }
        .heatmap-scroll::-webkit-scrollbar { height: 6px; }
        .heatmap-scroll::-webkit-scrollbar-thumb { background: var(--c-accent); border-radius: 4px; }

        .topic-row { display: flex; gap: 10px; margin-top: auto; }
        .topic-item { flex: 1; text-align: center; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; transition: 0.2s;}
        .topic-item:hover { background: rgba(255,255,255,0.1); }
        .topic-val { font-weight: 800; display: block; font-size: 1.1rem; }
        .topic-lbl { font-size: 0.65rem; text-transform: uppercase; color: #aebcc9; margin-top: 3px; }
        
        .month-list { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .month-item { background: rgba(0,0,0,0.2); padding: 8px; border-radius: 8px; text-align: center; }
        .month-name { font-size: 0.65rem; color: #aebcc9; text-transform: uppercase; display: block; margin-bottom: 4px;}
        .month-topic { font-weight: bold; font-size: 0.8rem; color: #fff; word-break: break-word; line-height: 1.2; background: rgba(255,255,255,0.1); padding: 2px 5px; border-radius: 4px; display: inline-block; margin: 1px;}

        .card-fact { justify-content: center; text-align: center; background: linear-gradient(135deg, #1B3C53, #234C6A); border: 2px dashed #456882; }
        .fact-text { font-size: 1.3rem; font-weight: 600; line-height: 1.4; color: #FFD700; font-family: 'Fredoka', sans-serif; }
        .chart-container { position: relative; height: 100%; min-height: 200px; }

        .action-area { display: flex; justify-content: center; gap: 20px; margin-top: 50px; animation: fadeInUp 1.5s ease-out; }
        .btn-download, .btn-secondary { border-radius: 50px; font-weight: 700; cursor: pointer; font-size: 1rem; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 12px 30px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .btn-download { background: linear-gradient(135deg, #25D366, #128C7E); color: white; border: none; box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3); }
        .btn-download:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 10px 25px rgba(37, 211, 102, 0.5); }
        .btn-secondary { background: transparent; border: 2px solid var(--c-text); color: var(--c-text); }
        .btn-secondary:hover { background: var(--c-text); color: var(--c-bg); transform: translateY(-3px); }

        /* ANIMATIONS */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

{{-- LOGIC PHP UNTUK COPYWRITING --}}
@php
    foreach($participants as $p) {
        // Hitung persentase moral
        $holy = $individualStats[$p]['holy'] ?? 0;
        $toxic = $individualStats[$p]['toxicity'] ?? 0;
        $totalMoral = $holy + $toxic;
        $pctHoly = $totalMoral > 0 ? round(($holy/$totalMoral)*100) : 50;
        
        $individualStats[$p]['capslock'] = $capslockCounts[$p] ?? 0;
        $individualStats[$p]['avg_len'] = $avgMsgLength[$p] ?? 0;
        $individualStats[$p]['rage'] = $rageTyping[$p] ?? 0;
        $individualStats[$p]['curious'] = $questionCounts[$p] ?? 0;
        $individualStats[$p]['insomnia'] = $lateNightCounts[$p] ?? 0;
        $individualStats[$p]['ghost'] = $deletedCounts[$p] ?? 0;
        $individualStats[$p]['influencer'] = $linkCounts[$p] ?? 0;
        
        // Data Tambahan untuk JS
        $individualStats[$p]['moral_pct_holy'] = $pctHoly;
        $individualStats[$p]['moral_pct_toxic'] = 100 - $pctHoly;
    }
@endphp

<div class="container">

    <div id="capture-area">
        <div class="header">
            <h1>CHAT WRAPPED</h1>
            <p>Edisi IDK WKWK</p>

            <div class="user-selector-container">
                <button onclick="switchMode('global')" class="btn-select active" id="btn-global">🌍(General)</button>
                @foreach($participants as $p)
                    <button onclick="switchMode('{{ $p }}')" class="btn-select" id="btn-{{ Str::slug($p) }}">{{ Str::limit($p, 10) }}</button>
                @endforeach
            </div>
        </div>

        <div class="grid-wrapper">

            <div class="card span-2 card-highlight" id="card-hero">
                <div class="label" style="color:#fff;" id="label-hero"><span class="icon">🌪️</span> Hari Paling Chaos</div>
                <div class="value" style="color:#fff;" id="val-hero">{{ $plotTwistDate }}</div>
                <div class="desc" style="color:#fff; opacity:0.9; border-color: rgba(255,255,255,0.3);" id="desc-hero">
                    Rekor <b>{{ number_format($plotTwistCount) }}</b> pesan dalam 24 jam. Jempol aman?
                </div>
            </div>

            <div class="card span-2 card-ai">
                <div class="label" style="color:#fff;" id="label-ai"><span class="icon">🔥</span> Kata AI Sih...</div>
                <div class="ai-text" id="val-ai">"{{ $aiResult['roast'] }}"</div>
                <div class="desc" style="color:#fff; opacity:0.7;" id="desc-ai">
                    Ramalan 2026: {{ $aiResult['prediction_2026'] }}
                </div>
            </div>

            <div class="card span-4" id="card-timeline">
                <div class="label"><span class="icon">📅</span> Seberapa Gabut Kita? (Heatmap)</div>
                <div class="heatmap-scroll">
                    <div class="heatmap-grid">
                        @foreach($heatmapData as $date => $count)
                            @php $color = $count > 50 ? '--c-heat-3' : ($count > 20 ? '--c-heat-2' : ($count > 0 ? '--c-heat-1' : '--c-heat-0')); @endphp
                            <div class="heat-box" style="background-color: var({{ $color }});" title="{{ $date }}: {{ $count }}"></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card span-2 row-2" id="card-hall-of-fame">
                <div class="label"><span class="icon">👑</span> Top Yappers (Paling Berisik)</div>
                <div style="flex:1; overflow-y: auto; padding-right:5px;">
                    @foreach($topUsers as $user => $count)
                        <div class="user-row {{ $loop->iteration <= 3 ? 'top-'.$loop->iteration : '' }}">
                            <div class="rank-num">#{{ $loop->iteration }}</div>
                            <div class="user-info">
                                <span class="user-name">{{ Str::limit($user, 18) }}</span>
                                <div class="user-badges">
                                    <span class="badge-pill">{{ $userPersonalities[$user] ?? 'Member' }}</span>
                                </div>
                            </div>
                            <div class="msg-count">{{ number_format($count) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="desc">Para donatur notifikasi terbesar di grup ini.</div>
            </div>

            <div class="card span-2 row-2" id="card-individual-summary" style="display: none; background: linear-gradient(135deg, #1B3C53, #234C6A); border: 1px solid #FFD700;">
                <div class="label" style="color: #FFD700;"><span class="icon">📊</span> Rapor Chat Kamu</div>
                <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 10px;">
                    <div>
                        <div class="label" style="margin-bottom: 5px;">Kontribusi Kebisingan</div>
                        <div class="value" id="summ-msg">-</div>
                    </div>
                    <div>
                        <div class="label" style="margin-bottom: 5px;">Jam Rawan (Paling Aktif)</div>
                        <div class="value" id="summ-time" style="font-size: 1.5rem;">-</div>
                        <div class="desc" style="margin-top:0;" id="summ-chrono">-</div>
                    </div>
                    <div>
                        <div class="label" style="margin-bottom: 5px;">Emoji Andalan</div>
                        <div class="value" id="summ-emoji">-</div>
                    </div>
                </div>
            </div>

            <div class="card span-2">
                <div class="label"><span class="icon">⚖️</span> Surga vs Neraka (Moral Meter)</div>
                @php
                    $totalMoral = $highestHoly + $highestToxic;
                    $pctHoly = $totalMoral > 0 ? ($highestHoly / $totalMoral) * 100 : 50;
                    $pctToxic = 100 - $pctHoly;
                @endphp
                <div class="moral-bar-container">
                    <div class="bar-holy" id="bar-holy" style="width: {{ $pctHoly }}%">{{ round($pctHoly) }}%</div>
                    <div class="bar-toxic" id="bar-toxic" style="width: {{ $pctToxic }}%">{{ round($pctToxic) }}%</div>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <div class="sub-value" style="color: #4ade80;" id="val-holy">😇 {{ Str::limit($mostHoly, 12) }}</div>
                        <div class="desc" style="border:none; padding:0; margin:0;" id="desc-holy">Paling Alim</div>
                    </div>
                    <div style="text-align: right;">
                        <div class="sub-value" style="color: #f87171;" id="val-toxic">😈 {{ Str::limit($mostToxic, 12) }}</div>
                        <div class="desc" style="border:none; padding:0; margin:0;" id="desc-toxic">Mulutnya Pedes</div>
                    </div>
                </div>
            </div>

            <div class="card span-2">
                <div class="label"><span class="icon">📁</span> Museum Konten</div>
                <div class="media-box">
                    <div class="media-item"><span class="media-icon">📷</span><div class="media-count" id="media-img">{{ $mediaStats['image'] }}</div><div class="media-name">FOTO</div></div>
                    <div class="media-item"><span class="media-icon">🎥</span><div class="media-count" id="media-vid">{{ $mediaStats['video'] }}</div><div class="media-name">VIDEO</div></div>
                    <div class="media-item"><span class="media-icon">💟</span><div class="media-count" id="media-stk">{{ $mediaStats['sticker'] }}</div><div class="media-name">STIKER</div></div>
                    <div class="media-item"><span class="media-icon">🎤</span><div class="media-count" id="media-aud">{{ $mediaStats['audio'] }}</div><div class="media-name">VN</div></div>
                </div>
                <div class="desc center" style="margin-top: 10px;" id="media-desc">
                    👑 Raja Stiker: <b style="color: #FFD700;">{{ $topStickerUser }}</b> (Bandar Stiker)
                </div>
            </div>

            <div class="card span-1" id="card-ngegas">
                <div class="label" id="lbl-ngegas"><span class="icon">📢</span> Tombol Capslock Jebol</div>
                <div class="value" style="font-size: 1.4rem;" id="val-ngegas">{{ Str::limit($topCapslock, 10) }}</div>
                <div class="desc" id="desc-ngegas">Juara ngegas, santai dikit napa.</div>
            </div>

            <div class="card span-1" id="card-novelis">
                <div class="label" id="lbl-novelis"><span class="icon">📝</span> Lagi Nulis Skripsi?</div>
                <div class="value" style="font-size: 1.4rem;" id="val-novelis">{{ Str::limit($topNovelist, 10) }}</div>
                <div class="desc" id="desc-novelis">Sekali chat panjang banget buset.</div>
            </div>

            <div class="card span-1" id="card-irit">
                <div class="label" id="lbl-irit"><span class="icon">🤐</span> Si Hemat Kuota</div>
                <div class="value" style="font-size: 1.4rem;" id="val-irit">{{ Str::limit($topSimple, 10) }}</div>
                <div class="desc" id="desc-irit">Chat singkat, padat, kadang gak jelas.</div>
            </div>

            <div class="card span-1" id="card-rage">
                <div class="label" id="lbl-rage"><span class="icon">😡</span> Jempol Senapan Mesin</div>
                <div class="value" style="font-size: 1.4rem;" id="val-rage">{{ Str::limit($topRage, 10) }}</div>
                <div class="desc" id="desc-rage">Spam chat beruntun tanpa napas.</div>
            </div>

            <div class="card span-1" id="card-curious">
                <div class="label" id="lbl-curious"><span class="icon">❓</span> Banyak Nanya (Kepo)</div>
                <div class="value" style="font-size: 1.4rem;" id="val-curious">{{ Str::limit($topCurious, 10) }}</div>
                <div class="desc" id="desc-curious">Paling sering bingung sendiri.</div>
            </div>

            <div class="card span-1" id="card-insomnia">
                <div class="label" id="lbl-insomnia"><span class="icon">🦇</span> Jam Tidur Rusak</div>
                <div class="value" style="font-size: 1.4rem;" id="val-insomnia">{{ Str::limit($topInsomnia, 10) }}</div>
                <div class="desc" id="desc-insomnia">Masih melek jam 2-5 pagi.</div>
            </div>

            <div class="card span-1" id="card-ghost">
                <div class="label" id="lbl-ghost"><span class="icon">👻</span> Duta Tarik Pesan</div>
                <div class="value" style="font-size: 1.4rem;" id="val-ghost">{{ Str::limit($topGhost, 10) }}</div>
                <div class="desc" id="desc-ghost">Hobi hapus chat. Mencurigakan.</div>
            </div>

            <div class="card span-1" id="card-influencer">
                <div class="label" id="lbl-influencer"><span class="icon">🔗</span> Suhu Share Link</div>
                <div class="value" style="font-size: 1.4rem;" id="val-influencer">{{ Str::limit($topInfluencer, 10) }}</div>
                <div class="desc" id="desc-influencer">Isi chatnya link TikTok/IG doang.</div>
            </div>

            <div class="card span-2">
                <div class="label"><span class="icon">📊</span> Isi Otak Grup Ini</div>
                <div class="topic-row">
                    <div class="topic-item"><span class="topic-val">{{ $topicStats['edukasi'] }}</span><span class="topic-lbl">Edukasi</span></div>
                    <div class="topic-item"><span class="topic-val">{{ $topicStats['receh'] }}</span><span class="topic-lbl">Receh</span></div>
                    <div class="topic-item"><span class="topic-val">{{ $topicStats['deep'] }}</span><span class="topic-lbl">Deep</span></div>
                    <div class="topic-item"><span class="topic-val">{{ $topicStats['gosip'] }}</span><span class="topic-lbl">romansa</span></div>
                </div>
            </div>

            <div class="card span-2 card-mc" style="background: linear-gradient(135deg, #FDB813, #F15A24);">
                <div class="label" style="color:#000;"><span class="icon">🌟</span> Main Character (MC)</div>
                <div class="value" style="color:#000;">{{ Str::limit($mainCharacter, 15) }}</div>
                <div class="desc" style="color:#000; border-color: rgba(0,0,0,0.2);">
                    Paling sering di-tag & cari perhatian. Aura protagonisnya kuat.
                </div>
            </div>

            <div class="card span-4 quote-card">
                <div class="quote-icon">“</div>
                <div class="label" style="color: #FFD700;">📜 Pesan Terpanjang (Cerpen)</div>
                <div class="quote-text">{{ $longestMessage['text'] }}</div>
                <div class="quote-author">
                    — {{ $longestMessage['by'] }}<br>
                    <span style="font-size:0.8rem; font-weight:normal; opacity:0.8;">Dikirim tanggal {{ $longestMessage['date'] }}</span>
                </div>
            </div>

            <div class="card span-3">
                <div class="label"><span class="icon">📉</span> Grafik Bipolar (Mood Swing)</div>
                <div class="chart-container"><canvas id="moodChart"></canvas></div>
            </div>
            
            <div class="card span-1" id="card-duo">
                <div class="label"><span class="icon">🤝</span> Bestie Sejati</div>
                <div class="value" style="font-size: 1.1rem;">{{ $topDuoName }}</div>
                <div class="desc" style="margin-top: auto;">{{ $topDuoCount }}x bales-balesan cepet (Cieee...).</div>
            </div>

            <div class="card span-2" style="background: linear-gradient(135deg, #1db954, #191414);">
                <div class="label" style="color:#fff;"><span class="icon">🎵</span> Lagu Kebangsaan</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: #fff;">{{ $aiResult['theme_song'] }}</div>
                <div class="desc" style="color:#fff; opacity:0.8;">Vibe Check by AI</div>
            </div>

            <div class="card span-2">
                <div class="label"><span class="icon">😂</span> Jokes Internal</div>
                <div class="value" style="font-size: 1.5rem;">"{{ $aiResult['inside_joke'] ?? '-' }}"</div>
                <div class="desc">Sering banget dibahas, padahal garing.</div>
            </div>

            <div class="card span-4">
                <div class="label"><span class="icon">📅</span> Fase Hidup Kita (Topik Bulanan)</div>
                <div class="month-list">
                    @foreach($monthlyTopics as $m => $topics)
                        <div class="month-item">
                            <span class="month-name">{{ date('F', mktime(0, 0, 0, $m, 10)) }}</span>
                            <div style="display:flex; flex-direction:column; gap:2px; margin-top:5px;">
                                @foreach($topics as $t) <span class="month-topic">{{ $t }}</span> @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card span-2" style="background: #000;">
                <div class="label"><span class="icon">🎨</span> Konsep Cover Album</div>
                <div style="color: #fff; font-family: monospace; font-size: 0.8rem; border: 1px dashed #fff; padding: 10px; margin-top: 10px;">
                    {{ Str::limit($albumPrompt, 150) }}
                </div>
            </div>
            
            <div class="card span-2 card-fact">
                <div class="label" style="justify-content: center; color: #aebcc9;"><span class="icon">🎲</span> Fakta Random</div>
                <div class="fact-text" id="fact-text">"{{ $randomFact }}"</div>
            </div>

        </div> 
    </div>
    
    <div class="action-area">
        <a href="{{ route('recap') }}" class="btn-secondary"><span class="icon">↺</span> Upload File Lain</a>
        <button onclick="downloadImage()" class="btn-download"><span class="icon">📸</span> Simpan Gambar</button>
    </div>

</div>

<script>
    // DATA DARI PHP
    const individualStats = @json($individualStats);
    
    // Simpan Data Global agar bisa tombol 'Global' diklik kembali
    const globalStats = {
        heroLabel: '<span class="icon">🌪️</span> Hari Paling Chaos',
        heroVal: "{{ $plotTwistDate }}",
        heroDesc: "Rekor <b>{{ number_format($plotTwistCount) }}</b> pesan dalam 24 jam. Jempol aman?",
        
        aiLabel: '<span class="icon">🔥</span> Kata AI Sih...',
        aiVal: `{!! addslashes($aiResult['roast']) !!}`,
        aiDesc: `Ramalan 2026: {!! addslashes($aiResult['prediction_2026']) !!}`,
        
        media: @json($mediaStats),
        mediaDesc: `👑 Raja Stiker: <b style="color: #FFD700;">{{ $topStickerUser }}</b> (Bandar Stiker)`,

        holyVal: "😇 {{ Str::limit($mostHoly, 12) }}",
        toxicVal: "😈 {{ Str::limit($mostToxic, 12) }}",
        holyPct: "{{ round($pctHoly) }}%",
        toxicPct: "{{ round($pctToxic) }}%",
        
        // COPYWRITING GLOBAL
        ngegasVal: "{{ Str::limit($topCapslock, 10) }}",
        novelisVal: "{{ Str::limit($topNovelist, 10) }}",
        iritVal: "{{ Str::limit($topSimple, 10) }}",
        rageVal: "{{ Str::limit($topRage, 10) }}",
        curiousVal: "{{ Str::limit($topCurious, 10) }}",
        insomniaVal: "{{ Str::limit($topInsomnia, 10) }}",
        ghostVal: "{{ Str::limit($topGhost, 10) }}",
        influencerVal: "{{ Str::limit($topInfluencer, 10) }}",

        fact: `{!! addslashes($randomFact) !!}`
    };

    function switchMode(user) {
        document.querySelectorAll('.btn-select').forEach(b => b.classList.remove('active'));

        // ELEMENTS TO TOGGLE
        const hallOfFame = document.getElementById('card-hall-of-fame');
        const timeline = document.getElementById('card-timeline');
        const indSummary = document.getElementById('card-individual-summary');
        const duoCard = document.getElementById('card-duo');

        if (user === 'global') {
            document.getElementById('btn-global').classList.add('active');
            
            // SHOW GLOBAL ELEMENTS, HIDE INDIVIDUAL
            hallOfFame.style.display = 'flex';
            timeline.style.display = 'block';
            duoCard.style.display = 'flex';
            indSummary.style.display = 'none';

            // RESTORE HERO & AI
            document.getElementById('label-hero').innerHTML = globalStats.heroLabel;
            document.getElementById('val-hero').innerHTML = globalStats.heroVal;
            document.getElementById('desc-hero').innerHTML = globalStats.heroDesc;
            document.getElementById('label-ai').innerHTML = globalStats.aiLabel;
            document.getElementById('val-ai').innerText = globalStats.aiVal;
            document.getElementById('desc-ai').innerText = globalStats.aiDesc;

            updateMedia(globalStats.media);
            document.getElementById('media-desc').innerHTML = globalStats.mediaDesc;
            
            // RESTORE MORAL
            updateMoral(globalStats.holyPct, globalStats.toxicPct, globalStats.holyVal, globalStats.toxicVal, "Paling Alim", "Mulutnya Pedes");

            // RESTORE FUN STATS (Global Champions)
            updateFunCard('ngegas', '📢 Tombol Capslock Jebol', globalStats.ngegasVal, 'Juara ngegas, santai dikit napa.');
            updateFunCard('novelis', '📝 Lagi Nulis Skripsi?', globalStats.novelisVal, 'Sekali chat panjang banget buset.');
            updateFunCard('irit', '🤐 Si Hemat Kuota', globalStats.iritVal, 'Chat singkat, padat, kadang gak jelas.');
            updateFunCard('rage', '😡 Jempol Senapan Mesin', globalStats.rageVal, 'Spam chat beruntun tanpa napas.');
            updateFunCard('curious', '❓ Banyak Nanya (Kepo)', globalStats.curiousVal, 'Paling sering bingung sendiri.');
            updateFunCard('insomnia', '🦇 Jam Tidur Rusak', globalStats.insomniaVal, 'Masih melek jam 2-5 pagi.');
            updateFunCard('ghost', '👻 Duta Tarik Pesan', globalStats.ghostVal, 'Hobi hapus chat. Mencurigakan.');
            updateFunCard('influencer', '🔗 Suhu Share Link', globalStats.influencerVal, 'Isi chatnya link TikTok/IG doang.');

            document.getElementById('fact-text').innerText = '"' + globalStats.fact + '"';

        } else {
            // MODE INDIVIDUAL
            const slug = user.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
            const btn = document.getElementById('btn-' + slug);
            if(btn) btn.classList.add('active');
            const data = individualStats[user];

            // HIDE GLOBAL SPECIFIC, SHOW INDIVIDUAL SUMMARY
            hallOfFame.style.display = 'none';
            timeline.style.display = 'none'; 
            duoCard.style.display = 'none';
            indSummary.style.display = 'flex';

            // ISI SUMMARY CARD
            document.getElementById('summ-msg').innerText = data.msg_count + " Chat";
            document.getElementById('summ-time').innerText = data.peak_time;
            document.getElementById('summ-chrono').innerText = "Tipe: " + data.chronotype;
            document.getElementById('summ-emoji').innerText = data.top_emoji;

            // UPDATE HERO (Top Words -> Kamus Bahasa User)
            document.getElementById('label-hero').innerHTML = '<span class="icon">🗣️</span> Kamus Besar Bahasa ' + user;
            document.getElementById('val-hero').innerText = '"' + data.top_words.join('" "') + '"';
            document.getElementById('desc-hero').innerText = "Kata-kata ini udah jadi ciri khas dia banget.";

            // UPDATE AI (Personality)
            document.getElementById('label-ai').innerHTML = '<span class="icon">🎭</span> Personality Check';
            document.getElementById('val-ai').innerText = "Tipe: " + data.personality;
            document.getElementById('desc-ai').innerText = "Dilihat dari cara ngetik & jam onlinenya.";

            updateMedia(data.media);
            document.getElementById('media-desc').innerHTML = "Stiker yang dikirim dia: <b>" + data.media.sticker + "</b>";

            // UPDATE MORAL (Individual Percentage)
            let pctH = data.moral_pct_holy + "%";
            let pctT = data.moral_pct_toxic + "%";
            updateMoral(pctH, pctT, "Sopan: "+data.holy, "Toxic: "+data.toxicity, "Kata Baik", "Kata Kotor");

            // UPDATE FUN STATS (Convert to Individual Stats - COPYWRITING PERSONAL)
            updateFunCard('ngegas', '🔥 Level Emosi', data.capslock + 'x', 'Kali kamu chat full CAPSLOCK.');
            updateFunCard('novelis', '📏 Rata-rata Ketikan', data.avg_len + ' huruf', 'Panjang chat kamu per bubble.');
            updateFunCard('irit', '⚡ Respon Kilat', '?', 'Kamu termasuk tim fast response.'); // Placeholder
            updateFunCard('rage', '💢 Momen Nyepam', data.rage + 'x', 'Kejadian kamu ngirim chat beruntun.');
            updateFunCard('curious', '🤔 Tingkat Kepo', data.curious + 'x', 'Tanda tanya yang kamu kirim.');
            updateFunCard('insomnia', '🌙 Begadang Check', data.insomnia + 'x', 'Chat yang kamu kirim jam 2-5 pagi.');
            updateFunCard('ghost', '🗑️ Jejak Dihapus', data.ghost + 'x', 'Pesan yang kamu tarik lagi.');
            updateFunCard('influencer', '🔗 Hobi Sharing', data.influencer + 'x', 'Link yang kamu sebar ke grup.');

            document.getElementById('fact-text').innerText = user + " paling bawel di jam " + data.peak_time + ".";
        }
    }

    function updateFunCard(id, label, val, desc) {
        document.getElementById('lbl-'+id).innerHTML = label;
        document.getElementById('val-'+id).innerText = val;
        document.getElementById('desc-'+id).innerText = desc;
    }

    function updateMoral(pctH, pctT, valH, valT, descH, descT) {
        document.getElementById('bar-holy').style.width = pctH; document.getElementById('bar-holy').innerText = pctH;
        document.getElementById('bar-toxic').style.width = pctT; document.getElementById('bar-toxic').innerText = pctT;
        document.getElementById('val-holy').innerText = valH; document.getElementById('val-toxic').innerText = valT;
        document.getElementById('desc-holy').innerText = descH; document.getElementById('desc-toxic').innerText = descT;
    }

    function updateMedia(data) {
        document.getElementById('media-img').innerText = data.image;
        document.getElementById('media-vid').innerText = data.video;
        document.getElementById('media-stk').innerText = data.sticker;
        document.getElementById('media-aud').innerText = data.audio;
    }

    function downloadImage() {
        const element = document.getElementById("capture-area");
        const btn = document.querySelector('.btn-download');
        const originalText = btn.innerHTML;
        btn.innerHTML = "⏳ Sabar...";
        html2canvas(element, { scale: 2, backgroundColor: "#1B3C53", useCORS: true }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Chat-Wrapped-Ultimate.png';
            link.href = canvas.toDataURL("image/png");
            link.click();
            btn.innerHTML = originalText;
        });
    }

    // Chart Setup
    const moodData = @json(array_values($sentimentMonthly));
    const labelsMonth = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const ctxMood = document.getElementById('moodChart').getContext('2d');
    new Chart(ctxMood, {
        type: 'line',
        data: {
            labels: labelsMonth,
            datasets: [
                { label: 'Positive Vibes', data: moodData.map(d => d.pos), borderColor: '#4ade80', backgroundColor: 'rgba(74, 222, 128, 0.2)', tension: 0.4, fill: true },
                { label: 'Negative Vibes', data: moodData.map(d => d.neg), borderColor: '#f87171', backgroundColor: 'rgba(248, 113, 113, 0.2)', tension: 0.4, fill: true }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { ticks: { color: '#aebcc9' }, grid: { display: false } }, y: { display: false } }, plugins: { legend: { labels: { color: '#fff' } } } }
    });
</script>

</body>
</html>