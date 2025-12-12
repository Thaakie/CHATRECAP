<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index()
    {
        return view('recap');
    }

    public function process(Request $request)
    {
        // 1. SETTINGS & VALIDATION
        mb_internal_encoding("UTF-8");
        ini_set('memory_limit', '512M'); 
        ini_set('max_execution_time', 300);

        $request->validate(['file' => 'required|mimetypes:text/plain,text/*']);
        $content = file_get_contents($request->file('file')->getRealPath());
        
        // Normalize newlines
        $lines = preg_split('/\r\n|\r|\n/', $content);
        
        // --- STORAGE VARIABLES ---
        $messages = [];
        $wordsMap = []; // Global
        $userWordsMap = []; // Per User
        $emojiMap = []; // Global
        $userEmojiMap = []; // Per User
        $userCounts = [];
        
        $hourlyCounts = array_fill(0, 24, 0);
        $dailyCounts  = ['Monday'=>0, 'Tuesday'=>0, 'Wednesday'=>0, 'Thursday'=>0, 'Friday'=>0, 'Saturday'=>0, 'Sunday'=>0];
        $monthlyCounts = array_fill(1, 12, 0); 
        $uniqueDays = [];
        $heatmapData = []; 

        $duoStats = []; 
        $sentimentMonthly = array_fill(1, 12, ['pos' => 0, 'neg' => 0]); 
        $wordsMonthly = array_fill(1, 12, []); 
        $initiatorAttempts = []; 
        $initiatorSuccess = []; 
        $pendingInitiator = null; 
        $pendingInitiatorTime = null;

        $topicStats = ['edukasi' => 0, 'receh' => 0, 'deep' => 0, 'gosip' => 0];
        $rageTyping = []; 
        $dailyNegatives = []; 
        $dailyVolume = []; 
        $questionCounts = [];
        $mentionCounts = [];
        $capslockCounts = [];
        $totalChars = [];
        $lateNightCounts = []; 
        $moralStats = []; 

        // Media Stats
        $userMediaStats = []; 
        $mediaStats = ['image' => 0, 'video' => 0, 'sticker' => 0, 'audio' => 0];
        $kingOfSticker = [];
        
        $linkCounts = [];
        $deletedCounts = [];
        $responseStats = []; 
        $userHourlyStats = []; 

        $prevDt = null;
        $prevSender = null;
        $longestMessage = ['text' => '', 'len' => 0, 'by' => '', 'date' => ''];
        
        $stopwords = ['yang', 'dan', 'di', 'ke', 'dari', 'ini', 'itu', 'aku', 'kamu', 'dia', 'kita', 'mereka', 'untuk', 'pada', 'adalah', 'sebagai', 'dengan', 'juga', 'karena', 'bahwa', 'tetapi', 'namun', 'atau', 'sudah', 'telah', 'sedang', 'akan', 'bisa', 'ada', 'tidak', 'tak', 'gak', 'ya', 'yah', 'ok', 'oke', 'yg', 'kalo', 'kalau', 'aja', 'saja', 'kok', 'sih', 'dong', 'deh', 'kan', 'mah', 'omitted', 'media', 'image', 'sticker', 'video', 'null', 'pesan', 'dihapus', 'missed', 'call', 'berakhir', 'hai', 'halo', 'bro', 'sis', 'kak', 'mas', 'mbak', 'pak', 'bu', 'dok', 'teruskan', 'forwarded', 'https', 'http', 'com', 'www', 'tuh', 'nih', 'deh', 'lah', 'doang', 'banget', 'bgt', 'gw', 'gue', 'lu', 'lo', 'apa', 'kenapa', 'gimana', 'kapan', 'tapi', 'bukan', 'sama', 'banyak', 'mau', 'ga', 'nggak', 'belom', 'belum'];

        $posWords = ['wkwk', 'haha', 'mantap', 'keren', 'makasih', 'love', 'bagus', 'selamat', 'alhamdulillah', 'gas', 'ayo', 'suka', 'rindu', 'kangen', 'baik', 'siap', 'oke', 'yoi', 'asik', 'bestie', 'gokil', 'wkwkwk'];
        $negWords = ['anjing', 'babi', 'bangsat', 'tolol', 'bodoh', 'sedih', 'sakit', 'kesel', 'marah', 'capek', 'pusing', 'males', 'gila', 'stres', 'kampret', 'sial', 'benci', 'ribut', 'emosi', 'tai', 'setan', 'fuck', 'shit'];
        $holyWords = ['maaf', 'makasih', 'terima kasih', 'tolong', 'mohon', 'alhamdulillah', 'insyaallah', 'assalamualaikum', 'syukur', 'sabar', 'amin', 'aamiin', 'semoga', 'nuhun', 'punten'];
        
        $topics = [
            'edukasi' => ['tugas', 'kelas', 'deadline', 'dosen', 'kuliah', 'kelompok', 'ppt', 'pdf', 'ujian', 'kumpul', 'absensi', 'presentasi'],
            'receh' => ['wkwk', 'haha', 'xixi', 'ngakak', 'meme', 'lucu', 'lol', 'receh', 'kocak', 'awokwok', 'bjir'],
            'deep' => ['hidup', 'capek', 'masa depan', 'nikah', 'kerja', 'sedih', 'maaf', 'berjuang', 'harapan', 'takut', 'perasaan', 'nangis'],
            'gosip' => ['dia', 'si itu', 'tau gak', 'ternyata', 'katanya', 'mantan', 'pacar', 'putus', 'jadian', 'spill', 'hot news']
        ];

// --- STEP 1: PARSING & NORMALIZATION ---
        $chatData = [];
        $buffer = null;

        // Bersihkan karakter strip aneh dari hasil copy-paste HP
        $content = str_replace(['–', '—', '−'], '-', $content);
        // Hapus karakter invisible yang sering bikin error regex
        $content = preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $content);
        $content = str_replace(["\u{202f}", "\u{00a0}", "\u{200e}", "\u{200f}"], ' ', $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // 1.5 DETEKSI FORMAT AM/PM (Dengan Koma & Unicode Space)
            // Pola: 6/29/25, 7:22 PM - Name: Message
            elseif (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{2,4}),?\s+(\d{1,2}:\d{2}.*?)\s-\s(.*)$/u', $line, $m)) {
                
                $dateRaw = $m[1];
                // Bersihkan karakter unicode 'Narrow No-Break Space' (\u{202F}) yang bikin error
                // Kita ganti semua karakter aneh di jam dengan spasi biasa
                $timeRaw = preg_replace('/[^\x20-\x7E]/', ' ', $m[2]); 
                $body    = $m[3]; 

                // Cek pesan enkripsi sistem (skip)
                if (str_contains($body, 'Messages and calls are end-to-end encrypted')) continue;

                // Cek Chat beneran (Ada tanda ": ")
                if (preg_match('/^(.*?):\s(.*)$/', $body, $msgMatch)) {
                    if ($buffer) $chatData[] = $buffer;

                    $buffer = [
                        'dateRaw' => $dateRaw,
                        'timeRaw' => $timeRaw,
                        'sender'  => trim($msgMatch[1]),
                        'text'    => trim($msgMatch[2]),
                        'format'  => 'wa_ampm' // Penanda format baru
                    ];
                }
            }
            // [TAMBAHAN BARU SELESAI]

            // 1. DETEKSI FORMAT UTAMA (HP KAMU)
            // Pola: 21/06/24 19.22 - Nama: Pesan
            // Regex ini menangkap: (Tanggal) (Jam.Menit) - (Sisanya)
            if (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{2,4})\s+(\d{1,2}\.\d{2})\s-\s(.*)$/', $line, $m)) {
                
                $dateRaw = $m[1];
                $timeRaw = str_replace('.', ':', $m[2]); // Ubah 19.22 jadi 19:22 untuk sistem
                $body    = $m[3]; // Isinya: "~Sajid: p" ATAU "Pesan terenkripsi..."

                // Cek apakah ini pesan enkripsi/sistem (tidak ada titik dua ": " setelah nama)
                if (str_contains($body, 'Pesan dan panggilan terenkripsi')) {
                    continue; // SKIP pesan ini
                }

                // Cek apakah ini Chat beneran (Ada Nama Pengirim dan Tanda ": ")
                if (preg_match('/^(.*?):\s(.*)$/', $body, $msgMatch)) {
                    // Simpan pesan sebelumnya (buffer) ke array
                    if ($buffer) $chatData[] = $buffer;

                    // Buat buffer baru
                    $buffer = [
                        'dateRaw' => $dateRaw,
                        'timeRaw' => $timeRaw,
                        'sender'  => trim($msgMatch[1]), // Nama (~Sajid)
                        'text'    => trim($msgMatch[2]), // Pesan (p)
                        'format'  => 'wa'
                    ];
                } 
                // Jika masuk sini tapi gak ada ":", berarti activity log (misal: "Sajid changed icon"), skip aja biar statistik gak rusak.
            }

            // 2. DETEKSI FORMAT DISCORD (Jaga-jaga)
            elseif (preg_match('/^\[(\d{2}\/\d{2}\/\d{4})\s+(\d{2}:\d{2})\]\s+(.*)$/', $line, $m)) {
                if ($buffer) $chatData[] = $buffer;
                $buffer = [
                    'dateRaw' => $m[1],
                    'timeRaw' => $m[2],
                    'sender'  => trim($m[3]),
                    'text'    => '',
                    'format'  => 'discord'
                ];
            }

            // 3. ISI PESAN (MULTILINE / KODINGAN / LINK)
            elseif ($buffer) {
                // Filter pesan sistem WA di baris sambungan
                if (str_contains($line, 'Pesan dan panggilan terenkripsi')) continue;

                // Normalisasi penanda media supaya terhitung di statistik
                if (str_contains($line, '<Media tidak disertakan>') || str_contains($line, 'Media tidak disertakan')) {
                    $line = "image omitted"; // Ubah ke keyword standar biar kehitung statistik gambar
                }

                // GABUNGKAN PESAN
                // Pakai "\n" (Enter) supaya kodingan C kamu tetap rapi ke bawah, bukan menyamping
                if ($buffer['text'] === '') {
                    $buffer['text'] = $line;
                } else {
                    $buffer['text'] .= "\n" . $line;
                }
            }
        }
        // Masukkan pesan terakhir
        if ($buffer) $chatData[] = $buffer;

        // --- STEP 2: ANALISIS ---
        foreach ($chatData as $data) {
            $sender = trim($data['sender']);
            $text   = trim($data['text']);
            
            try { 
                if (isset($data['format']) && $data['format'] === 'discord') {
                    $dt = Carbon::createFromFormat('d/m/Y H:i', $data['dateRaw'] . ' ' . $data['timeRaw']);
                } else {
                    $dt = Carbon::parse($data['dateRaw'] . ' ' . $data['timeRaw']); 
                }
            } catch (\Exception $e) { continue; }
            
            $monthIdx = (int)$dt->format('n');
            $dateKey = $dt->format('Y-m-d');
            $lowerText = strtolower($text);
            $hour = (int)$dt->format('G');

            // --- DATA COLLECTING ---
            if (!isset($heatmapData[$dateKey])) $heatmapData[$dateKey] = 0; $heatmapData[$dateKey]++;
            if (!isset($dailyVolume[$dateKey])) $dailyVolume[$dateKey] = 0; $dailyVolume[$dateKey]++;
            if (!isset($totalChars[$sender])) $totalChars[$sender] = 0; $totalChars[$sender] += mb_strlen($text);

            // Inisialisasi array per user
            if (!isset($userMediaStats[$sender])) $userMediaStats[$sender] = ['image'=>0, 'video'=>0, 'sticker'=>0, 'audio'=>0];
            if (!isset($userEmojiMap[$sender])) $userEmojiMap[$sender] = [];
            if (!isset($moralStats[$sender])) $moralStats[$sender] = ['holy' => 0, 'toxic' => 0];

            // Topics
            foreach ($topics as $cat => $keywords) {
                foreach ($keywords as $k) {
                    if (str_contains($lowerText, $k)) { $topicStats[$cat]++; break; }
                }
            }
            // Fight Meter
            foreach ($negWords as $nw) {
                if (str_contains($lowerText, $nw)) {
                    if (!isset($dailyNegatives[$dateKey])) $dailyNegatives[$dateKey] = 0; $dailyNegatives[$dateKey]++;
                }
            }
            // Moral
            foreach ($holyWords as $hw) { if (str_contains($lowerText, $hw)) $moralStats[$sender]['holy']++; }
            foreach ($negWords as $nw) { if (str_contains($lowerText, $nw)) $moralStats[$sender]['toxic']++; }

            // Rage Typing
            if ($prevDt && $prevSender === $sender) {
                $diffSec = $dt->diffInSeconds($prevDt);
                if ($diffSec < 10 && $hour >= 22) {
                    if (!isset($rageTyping[$sender])) $rageTyping[$sender] = 0; $rageTyping[$sender]++;
                }
            }

            // Sleep & Capslock
            if ($hour >= 2 && $hour < 5) {
                if (!isset($lateNightCounts[$sender])) $lateNightCounts[$sender] = 0; $lateNightCounts[$sender]++;
            }
            if (mb_strlen($text) > 4 && !str_contains($lowerText, 'omitted')) {
                $upperCount = preg_match_all('/[A-Z]/', $text);
                $letterCount = preg_match_all('/[a-zA-Z]/', $text);
                if ($letterCount > 0 && ($upperCount / $letterCount) > 0.6) {
                    if (!isset($capslockCounts[$sender])) $capslockCounts[$sender] = 0; $capslockCounts[$sender]++;
                }
            }

            // Media & Words
            if (str_contains($lowerText, 'sticker omitted') || str_contains($lowerText, '.webp')) {
                $mediaStats['sticker']++; $userMediaStats[$sender]['sticker']++;
                if (!isset($kingOfSticker[$sender])) $kingOfSticker[$sender] = 0; $kingOfSticker[$sender]++;
            } elseif (str_contains($lowerText, 'image omitted') || str_contains($lowerText, '.jpg') || str_contains($lowerText, '.png')) {
                $mediaStats['image']++; $userMediaStats[$sender]['image']++;
            } elseif (str_contains($lowerText, 'video omitted') || str_contains($lowerText, '.mp4')) {
                $mediaStats['video']++; $userMediaStats[$sender]['video']++;
            } elseif (str_contains($lowerText, 'audio omitted') || str_contains($lowerText, 'ptt')) {
                $mediaStats['audio']++; $userMediaStats[$sender]['audio']++;
            } else {
                if (mb_strlen($text) > $longestMessage['len'] && !str_contains($lowerText, 'omitted')) {
                    $longestMessage = ['text' => $text, 'len' => mb_strlen($text), 'by' => $sender, 'date' => $dt->format('d M Y')];
                }
                $cleanText = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $text));
                $words = explode(' ', $cleanText);
                foreach ($words as $w) {
                    if (mb_strlen($w) > 2 && !in_array($w, $stopwords)) {
                        if (!isset($wordsMap[$w])) $wordsMap[$w] = 0; $wordsMap[$w]++;
                        if (!isset($userWordsMap[$sender][$w])) $userWordsMap[$sender][$w] = 0; $userWordsMap[$sender][$w]++;
                        if (!isset($wordsMonthly[$monthIdx][$w])) $wordsMonthly[$monthIdx][$w] = 0; $wordsMonthly[$monthIdx][$w]++;
                    }
                }
                preg_match_all('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}]/u', $text, $matches);
                foreach ($matches[0] as $emoji) {
                    if (!isset($emojiMap[$emoji])) $emojiMap[$emoji] = 0; $emojiMap[$emoji]++;
                    if (!isset($userEmojiMap[$sender][$emoji])) $userEmojiMap[$sender][$emoji] = 0; $userEmojiMap[$sender][$emoji]++;
                }
            }

            // Curious, Mention, Link, Delete
            $questionCounts[$sender] = ($questionCounts[$sender] ?? 0) + substr_count($text, '?');
            $mentionCounts[$sender] = ($mentionCounts[$sender] ?? 0) + substr_count($text, '@');
            if (str_contains($lowerText, 'pesan ini telah dihapus') || str_contains($lowerText, 'this message was deleted')) {
                if (!isset($deletedCounts[$sender])) $deletedCounts[$sender] = 0; $deletedCounts[$sender]++;
            }
            if (str_contains($text, 'http://') || str_contains($text, 'https://') || str_contains($text, 'www.')) {
                if (!isset($linkCounts[$sender])) $linkCounts[$sender] = 0; $linkCounts[$sender]++;
            }

            // Interaction
            if ($prevDt) {
                $diffInMinutes = $dt->diffInMinutes($prevDt);
                if ($diffInMinutes > 240) { 
                    if (!isset($initiatorAttempts[$sender])) $initiatorAttempts[$sender] = 0; $initiatorAttempts[$sender]++;
                    $pendingInitiator = $sender; $pendingInitiatorTime = $dt;
                } elseif ($pendingInitiator && $sender !== $pendingInitiator && $dt->diffInMinutes($pendingInitiatorTime) < 60) {
                    if (!isset($initiatorSuccess[$pendingInitiator])) $initiatorSuccess[$pendingInitiator] = 0; $initiatorSuccess[$pendingInitiator]++;
                    $pendingInitiator = null; 
                }
                if ($prevSender && $prevSender !== $sender && $diffInMinutes < 2) {
                    $pair = [$prevSender, $sender]; sort($pair); $pairKey = implode('|', $pair);
                    if (!isset($duoStats[$pairKey])) $duoStats[$pairKey] = 0; $duoStats[$pairKey]++;
                }
                if ($prevSender && $prevSender !== $sender && $diffInMinutes < 120) {
                    if (!isset($responseStats[$sender])) $responseStats[$sender] = ['total' => 0, 'count' => 0];
                    $responseStats[$sender]['total'] += $dt->diffInSeconds($prevDt);
                    $responseStats[$sender]['count']++;
                }
            }

            foreach ($posWords as $pw) { if (str_contains($lowerText, $pw)) $sentimentMonthly[$monthIdx]['pos']++; }
            foreach ($negWords as $nw) { if (str_contains($lowerText, $nw)) $sentimentMonthly[$monthIdx]['neg']++; }

            if (!isset($userHourlyStats[$sender])) $userHourlyStats[$sender] = array_fill(0, 24, 0);
            $userHourlyStats[$sender][$hour]++;

            $messages[] = ['ts' => $dt, 'sender' => $sender, 'text' => $text];
            if (!isset($userCounts[$sender])) $userCounts[$sender] = 0;
            $userCounts[$sender]++;
            
            $hourlyCounts[$hour]++;
            $dailyCounts[$dt->format('l')]++;
            $monthlyCounts[$monthIdx]++;
            $uniqueDays[$dt->format('Y-m-d')] = true;
            $prevDt = $dt;
            $prevSender = $sender;
        }

        // --- CHECK IF DATA IS EMPTY ---
        if (count($messages) === 0) {
            dd("OOPS! Data masih 0. Format chat tidak dikenali.", array_slice($lines, 0, 10));
        }

        // --- SORTING ---
        arsort($userCounts); arsort($wordsMap); arsort($emojiMap); arsort($duoStats);
        arsort($initiatorAttempts); arsort($linkCounts); arsort($deletedCounts); arsort($kingOfSticker); 
        arsort($capslockCounts); arsort($questionCounts); arsort($mentionCounts); arsort($lateNightCounts);
        arsort($rageTyping); arsort($dailyVolume); arsort($dailyNegatives);
        
        $topUsers = array_slice($userCounts, 0, 5);
        $topWords = array_slice($wordsMap, 0, 15);
        $topEmojis = array_slice($emojiMap, 0, 5);

        // PREPARE INDIVIDUAL STATS FOR JS
        $participants = array_keys($userCounts);
        $individualStats = [];
        foreach ($participants as $p) {
            $myWords = $userWordsMap[$p] ?? []; arsort($myWords);
            $top3Words = array_slice(array_keys($myWords), 0, 3);
            
            $myEmojis = $userEmojiMap[$p] ?? []; arsort($myEmojis);
            $topEmoji = array_key_first($myEmojis) ?? '-';

            $hours = $userHourlyStats[$p] ?? array_fill(0, 24, 0);
            $peakHour = array_search(max($hours), $hours);
            $chrono = ($peakHour >= 5 && $peakHour < 11) ? 'Pagi' : (($peakHour >= 11 && $peakHour < 18) ? 'Siang' : 'Malam');
            $peakTimeStr = sprintf("%02d:00", $peakHour);

            $traits = [];
            if (($capslockCounts[$p]??0) > 10) $traits[] = "Ngegas 🔥";
            if (($moralStats[$p]['holy']??0) > 10) $traits[] = "Alim 😇";
            if (($moralStats[$p]['toxic']??0) > 10) $traits[] = "Toxic 😈";
            if (($lateNightCounts[$p]??0) > 5) $traits[] = "Kalong 🦇";
            if (empty($traits)) $traits[] = "Normal 😐";

            $individualStats[$p] = [
                'msg_count' => number_format($userCounts[$p]),
                'top_words' => $top3Words,
                'top_emoji' => $topEmoji,
                'peak_time' => $peakTimeStr,
                'chronotype' => $chrono,
                'media' => $userMediaStats[$p] ?? ['image'=>0,'video'=>0,'sticker'=>0,'audio'=>0],
                'toxicity' => $moralStats[$p]['toxic'] ?? 0,
                'holy' => $moralStats[$p]['holy'] ?? 0,
                'personality' => $traits[0]
            ];
        }

        // Global Stats Continued
        $moodSwingMonth = '-'; $maxSwing = 0;
        foreach ($sentimentMonthly as $m => $data) {
            $total = $data['pos'] + $data['neg'];
            if ($total > $maxSwing) { $maxSwing = $total; $moodSwingMonth = Carbon::createFromFormat('!m', $m)->format('F'); }
        }

        $userPersonalities = [];
        foreach ($topUsers as $u => $c) {
            $userPersonalities[$u] = $individualStats[$u]['personality'];
        }

        $plotTwistDate = '-'; $plotTwistCount = 0;
        if (!empty($dailyVolume)) {
            $plotTwistCount = reset($dailyVolume);
            $plotTwistDate = Carbon::parse(array_key_first($dailyVolume))->isoFormat('D MMMM Y');
        }

        $fightDate = '-'; $fightCount = 0;
        if (!empty($dailyNegatives)) {
            $fightCount = reset($dailyNegatives);
            $fightDate = Carbon::parse(array_key_first($dailyNegatives))->isoFormat('D MMMM Y');
        }

        $mcScores = [];
        foreach ($userCounts as $u => $c) {
            $mcScores[$u] = ($c * 0.5) + (($mentionCounts[$u]??0) * 5) + (($initiatorAttempts[$u]??0) * 3);
        }
        arsort($mcScores); $mainCharacter = array_key_first($mcScores) ?? '-';

        $facts = ["Kamu lebih banyak pake emoji daripada titik.", "Hari favorit kalian adalah ".array_search(max($dailyCounts), $dailyCounts)."."];
        $randomFact = $facts[array_rand($facts)];

        $avgMsgLength = [];
        foreach ($totalChars as $u => $total) {
            if (isset($userCounts[$u]) && $userCounts[$u] > 5) $avgMsgLength[$u] = round($total / $userCounts[$u]);
        }
        arsort($avgMsgLength); $topNovelist = array_key_first($avgMsgLength) ?? '-'; 
        asort($avgMsgLength); $topSimple = array_key_first($avgMsgLength) ?? '-';

        $reviverRates = [];
        foreach ($initiatorAttempts as $u => $attempts) {
            if ($attempts > 2) {
                $reviverRates[$u] = round((($initiatorSuccess[$u] ?? 0) / $attempts) * 100, 1);
            }
        }
        arsort($reviverRates); $topReviver = array_key_first($reviverRates) ?? '-';

        $monthlyTopics = [];
        foreach ($wordsMonthly as $m => $words) {
            if (!empty($words)) { arsort($words); $monthlyTopics[$m] = array_slice(array_keys($words), 0, 3); } 
            else { $monthlyTopics[$m] = []; }
        }

        $topDuoName = '-'; $topDuoCount = 0;
        if (!empty($duoStats)) {
            $topDuoKey = array_key_first($duoStats);
            $topDuoName = str_replace('|', ' & ', $topDuoKey);
            $topDuoCount = reset($duoStats);
        }

        $avgResponseTimes = [];
        foreach ($responseStats as $u => $data) {
            if ($data['count'] > 5) $avgResponseTimes[$u] = round($data['total'] / $data['count']);
        }
        asort($avgResponseTimes); $fastestResponders = array_slice($avgResponseTimes, 0, 3);
        
        $topInfluencer = array_key_first($linkCounts) ?? '-';
        $topGhost = array_key_first($deletedCounts) ?? '-';
        $topStickerUser = array_key_first($kingOfSticker) ?? '-';
        $topCapslock = array_key_first($capslockCounts) ?? '-';
        $topCurious = array_key_first($questionCounts) ?? '-';
        $topRage = array_key_first($rageTyping) ?? '-';
        $topInsomnia = array_key_first($lateNightCounts) ?? '-';
        
        $mostHoly = '-'; $highestHoly = -1;
        $mostToxic = '-'; $highestToxic = -1;
        foreach ($moralStats as $u => $s) {
            if ($s['holy'] > $highestHoly) { $highestHoly = $s['holy']; $mostHoly = $u; }
            if ($s['toxic'] > $highestToxic) { $highestToxic = $s['toxic']; $mostToxic = $u; }
        }

        $mostActiveHourVal = max($hourlyCounts);
        $mostActiveHourKey = array_search($mostActiveHourVal, $hourlyCounts);
        $mostActiveHourStr = sprintf("%02d:00 - %02d:00", $mostActiveHourKey, $mostActiveHourKey + 1);

        $streak = 0; $maxStreak = 0; $prevDate = null;
        $dates = array_keys($uniqueDays); sort($dates);
        if (!empty($dates)) {
            $startDate = Carbon::parse($dates[0]);
            $endDate = Carbon::parse(end($dates));
            $tempDate = $startDate->copy();
            while ($tempDate->lte($endDate)) {
                $dKey = $tempDate->format('Y-m-d');
                if (!isset($heatmapData[$dKey])) $heatmapData[$dKey] = 0;
                $tempDate->addDay();
            }
            ksort($heatmapData);
        }
        foreach ($dates as $dateStr) {
            $currDate = Carbon::parse($dateStr);
            if ($prevDate && $prevDate->diffInDays($currDate) == 1) $streak++; else $streak = 1;
            if ($streak > $maxStreak) $maxStreak = $streak;
            $prevDate = $currDate;
        }
        $totalMessages = count($messages);
        $avgPerDay = $totalMessages > 0 ? round($totalMessages / (count($uniqueDays) ?: 1), 1) : 0;

        // AI GROQ
        $aiResult = ['summary'=>'AI Error/Limit.', 'personality'=>'Misterius', 'caption'=>'#Wrapped', 'inside_joke'=>'-', 'roast'=>'Grup biasa aja.', 'theme_song'=>'Hening Cipta', 'prediction_2026'=>'Sepi.'];
        $apikey = env('GROQ_API_KEY');

        $chatSample = array_slice($messages, -80); 
        $textToSend = "";
        foreach ($chatSample as $m) $textToSend .= $m['sender'] . ": " . $m['text'] . "\n";
        $topTopic = array_key_first($wordsMap) ?? 'Chat';
        $albumPrompt = "Album cover art featuring abstract representation of '$topTopic' with vibrant colors";

        if (!empty($textToSend)) {
            try {
                $prompt = "Analisis chat ini. Output JSON only. Keys: 'summary' (ringkasan), 'personality' (3 kata sifat), 'caption' (IG story), 'inside_joke' (frasa unik), 'roast' (ejekan pedas), 'theme_song' (lagu+artis), 'prediction_2026' (ramalan lucu). Log: $textToSend";
                $response = Http::withoutVerifying()->withToken($apiKey)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'system', 'content' => 'JSON output only.'], ['role' => 'user', 'content' => $prompt]],
                    'response_format' => ['type' => 'json_object']
                ]);
                if ($response->successful()) {
                    $data = json_decode($response->json()['choices'][0]['message']['content'], true);
                    if ($data) $aiResult = array_merge($aiResult, $data);
                }
            } catch (\Exception $e) {}
        }

        return view('recap-result', compact(
            'individualStats', 'participants',
            'totalMessages', 'avgPerDay', 'maxStreak', 'mostActiveHourStr', 
            'topUsers', 'topWords', 'topEmojis', 'mediaStats', 'hourlyCounts',
            'aiResult', 'topStickerUser', 'kingOfSticker',
            'topInfluencer', 'linkCounts', 'topGhost', 'deletedCounts',
            'fastestResponders', 'userPersonalities',
            'topCapslock', 'capslockCounts', 'heatmapData',
            'topNovelist', 'avgMsgLength', 'topSimple',
            'topDuoName', 'topDuoCount', 'topReviver', 'reviverRates',
            'sentimentMonthly', 'monthlyTopics',
            'plotTwistDate', 'plotTwistCount',
            'topCurious', 'questionCounts', 'mainCharacter', 'mcScores',
            'topInsomnia', 'lateNightCounts', 'topRage', 'rageTyping',
            'mostHoly', 'highestHoly', 'mostToxic', 'highestToxic',
            'fightDate', 'fightCount', 'longestMessage', 'randomFact',
            'topicStats', 'moodSwingMonth', 'albumPrompt'
        ));
    }
}