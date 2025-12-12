<?php

namespace App\Services;

use Carbon\Carbon;

class ChatParser
{
    /**
     * Daftar Pola Regex untuk mendeteksi baris baru chat.
     * Urutan penting: Dari yang paling spesifik ke yang umum.
     */
    private $patterns = [
        // 1. FORMAT CUSTOM (Sesuai request kamu sebelumnya)
        // Contoh: blackiee - 23/04/2025 20:41
        'custom' => [
            'regex' => '/^(.*?)\s-\s(\d{2}[\/\.]\d{2}[\/\.]\d{4})\s(\d{2}[:\.]\d{2})$/',
            'map'   => ['sender' => 1, 'date' => 2, 'time' => 3, 'text' => null] // Text null artinya ada di baris berikutnya
        ],

        // 2. WHATSAPP IOS / BRACKET (Dengan atau tanpa detik)
        // Contoh: [21/06/24 19.22.10] Sajid: Halo
        // Contoh: [6/21/24, 7:22 PM] Sajid: Halo
        'wa_bracket' => [
            'regex' => '/^\[(\d{1,2}[\/\.-]\d{1,2}[\/\.-]\d{2,4}),?\s+(\d{1,2}[:\.]\d{2}(?::\d{2})?(?:\s?[APap][Mm])?)\]\s*(.*?):\s*(.*)$/',
            'map'   => ['date' => 1, 'time' => 2, 'sender' => 3, 'text' => 4]
        ],

        // 3. WHATSAPP ANDROID / STANDARD (Format Indo/UK)
        // Contoh: 21/06/24 19.22 - Sajid: Halo
        // Contoh: 6/21/24, 7:22 PM - Sajid: Halo
        'wa_standard' => [
            'regex' => '/^(\d{1,2}[\/\.-]\d{1,2}[\/\.-]\d{2,4}),?\s+(\d{1,2}[:\.]\d{2}(?::\d{2})?(?:\s?[APap][Mm])?)\s*-\s*(.*?):\s*(.*)$/',
            'map'   => ['date' => 1, 'time' => 2, 'sender' => 3, 'text' => 4]
        ],

        // 4. LINE CHAT EXPORT
        // Contoh: 19:22 Sajid Halo (Biasanya didahului header tanggal, tapi ini basic parsing per baris)
        // Format Line agak tricky karena tanggalnya beda baris, tapi kita coba tangkap pola waktu + tab/spasi + nama
        'line_basic' => [
            'regex' => '/^(\d{1,2}:\d{2})\s+([^\t\n]+)[\t\s](.*)$/',
            'map'   => ['time' => 1, 'sender' => 2, 'text' => 3, 'date' => 'CONTEXT_DATE'] // Perlu logika khusus
        ],
    ];

    public function parse($file)
    {
        $mime = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $path = $file->getRealPath();

        if ($extension === 'json' || $mime === 'application/json') {
            return $this->parseJson($path);
        } else {
            return $this->parseTxt($path);
        }
    }

    private function parseTxt($path)
    {
        $content = file_get_contents($path);
        // Fix encoding
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        // Split lines
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $messages = [];
        $buffer = null;
        $contextDate = Carbon::now()->format('Y-m-d'); // Default untuk format chat yang tidak punya tanggal di tiap baris (spt Line)

        foreach ($lines as $line) {
            $line = $this->cleanLine($line);
            if (empty($line)) continue;

            // Cek Header Tanggal (Khusus LINE Chat atau format yang tanggalnya dipisah)
            // Contoh Line: 2023.04.23 Sunday
            if (preg_match('/^(\d{4})[\.\/](\d{1,2})[\.\/](\d{1,2})/', $line, $dateMatch)) {
                $contextDate = "{$dateMatch[1]}-{$dateMatch[2]}-{$dateMatch[3]}";
                continue; // Skip baris ini karena cuma header tanggal
            }

            $matched = false;

            // LOOP SEMUA POLA REGEX
            foreach ($this->patterns as $type => $p) {
                if (preg_match($p['regex'], $line, $m)) {
                    
                    // Simpan buffer pesan sebelumnya
                    if ($buffer) {
                        $messages[] = $buffer;
                        $buffer = null;
                    }

                    // Ekstrak data berdasarkan peta (map)
                    $dateRaw = ($p['map']['date'] === 'CONTEXT_DATE') ? $contextDate : ($m[$p['map']['date']] ?? null);
                    $timeRaw = $m[$p['map']['time']] ?? null;
                    $sender  = trim($m[$p['map']['sender']]);
                    $text    = isset($p['map']['text']) ? trim($m[$p['map']['text']]) : ''; 

                    // Skip System Messages (Enkripsi, Grup dibuat, dsb)
                    // Biasanya sender kosong atau mengandung kata kunci sistem jika regex salah tangkap
                    if ($this->isSystemMessage($sender, $text)) {
                        $matched = true; // Tandai matched biar ga masuk ke buffer text
                        break; 
                    }

                    // Parsing Tanggal & Waktu
                    try {
                        $ts = $this->parseDateTime($dateRaw, $timeRaw);
                    } catch (\Exception $e) {
                        // Jika gagal parse tanggal, anggap ini bukan header pesan, lanjut loop
                        continue;
                    }

                    $buffer = [
                        'ts'       => $ts,
                        'sender'   => $sender,
                        'text'     => $text,
                        'platform' => $type
                    ];

                    $matched = true;
                    break; // Keluar dari loop patterns, lanjut baris berikutnya
                }
            }

            // JIKA TIDAK COCOK DENGAN POLA APAPUN -> APPEND KE BUFFER (MULTILINE)
            if (!$matched && $buffer) {
                // Handle pesan "omitted"
                if (str_contains($line, 'sticker omitted') || str_contains($line, 'Media tidak disertakan')) {
                    $line = "<Sticker>";
                } elseif (str_contains($line, 'image omitted')) {
                    $line = "<Image>";
                }

                if (empty($buffer['text'])) {
                    $buffer['text'] = $line;
                } else {
                    $buffer['text'] .= "\n" . $line; // Pakai \n biar format paragraf terjaga
                }
            }
        }

        // Simpan pesan terakhir
        if ($buffer) $messages[] = $buffer;

        return $messages;
    }

    /**
     * Helper: Parse JSON (Telegram/Discord) - Tidak banyak berubah
     */
    private function parseJson($path)
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        $messages = [];

        // TELEGRAM
        if (isset($data['messages'])) {
            foreach ($data['messages'] as $msg) {
                if ($msg['type'] !== 'message') continue;
                
                $sender = $msg['from'] ?? $msg['actor'] ?? 'Unknown';
                $textRaw = $msg['text'];
                $text = '';
                
                if (is_array($textRaw)) {
                    foreach ($textRaw as $part) {
                        $text .= is_array($part) ? ($part['text'] ?? '') : $part;
                    }
                } else {
                    $text = (string)$textRaw;
                }

                if (empty($text) && isset($msg['media_type'])) $text = "<Media: {$msg['media_type']}>";

                try {
                    $messages[] = [
                        'ts' => Carbon::parse($msg['date']),
                        'sender' => $sender,
                        'text' => $text,
                        'platform' => 'telegram'
                    ];
                } catch (\Exception $e) {}
            }
        }
        // DISCORD
        elseif (is_array($data)) {
            foreach ($data as $msg) {
                if (!isset($msg['timestamp'])) continue;
                $messages[] = [
                    'ts' => Carbon::parse($msg['timestamp']),
                    'sender' => $msg['author']['name'] ?? 'Unknown',
                    'text' => $msg['content'] ?? '',
                    'platform' => 'discord'
                ];
            }
        }

        return $messages;
    }

    /**
     * Helper: Bersihkan String
     */
    private function cleanLine($line)
    {
        $line = trim($line);
        $line = preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $line);
        return str_replace(["\u{202f}", "\u{00a0}", "\u{200e}", "\u{200f}"], ' ', $line);
    }

    /**
     * Helper: Cerdas Parse Tanggal & Waktu
     */
    private function parseDateTime($dateStr, $timeStr)
    {
        // Normalisasi separator: ganti titik dengan titik dua untuk waktu, dsb.
        $timeStr = str_replace('.', ':', $timeStr); 
        $dateStr = str_replace(['.', '-'], '/', $dateStr);
        
        // Gabung
        $fullString = "$dateStr $timeStr";

        // Coba parse dengan Carbon secara fleksibel
        // Carbon cukup pintar menangani "21/06/24" vs "6/21/24" tergantung locale, 
        // tapi kadang perlu dipaksa formatnya jika error.
        try {
            return Carbon::parse($fullString);
        } catch (\Exception $e) {
            // Fallback manual jika format US (mm/dd/yy) gagal di parse sbg (dd/mm/yy)
            return Carbon::createFromFormat('m/d/y H:i', $fullString);
        }
    }

    /**
     * Helper: Filter Pesan Sistem WhatsApp
     */
    private function isSystemMessage($sender, $text)
    {
        // Pesan enkripsi tidak punya sender di pola bracket tertentu, 
        // atau sender-nya adalah teks enkripsi itu sendiri
        $keywords = [
            'Messages and calls are end-to-end encrypted',
            'Pesan dan panggilan terenkripsi',
            'created group',
            'membuat grup',
            'added',
            'menambahkan',
            'left',
            'keluar',
            'changed',
            'mengubah'
        ];

        // Jika sender mengandung kata kerja sistem (kasus WA Android kadang: "Anda mengubah ikon...")
        foreach ($keywords as $k) {
            if (stripos($sender, $k) !== false || stripos($text, $k) !== false) {
                return true;
            }
        }
        return false;
    }
}