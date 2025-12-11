<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

class ChatParser
{
    /**
     * Main Entry Point: Terima File -> Keluar Array Pesan Standar
     */
    public function parse($file)
    {
        $mime = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $path = $file->getRealPath();

        // 1. DETEKSI BERDASARKAN EKSTENSI
        if ($extension === 'json' || $mime === 'application/json') {
            return $this->parseJson($path);
        } else {
            // Default TXT (WhatsApp / Line / Custom)
            return $this->parseTxt($path);
        }
    }

    /**
     * Penanganan File Text (WhatsApp / Custom Format)
     * Menggunakan sistem BUFFER untuk menangani pesan multi-baris
     */
    private function parseTxt($path)
    {
        $content = file_get_contents($path);
        
        // AUTO-FIX ENCODING
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $messages = [];
        $buffer = null; // Variabel penampung sementara

        foreach ($lines as $line) {
            $line = $this->cleanLine($line);
            if (empty($line)) continue;

            // --- A. DETEKSI FORMAT CUSTOM (Format Screenshot Kamu) ---
            // Pola: Nama - dd/mm/yyyy HH:mm (Di baris sendiri)
            // Contoh: blackiee - 23/04/2025 20:41
            if (preg_match('/^(.*?)\s-\s(\d{2}\/\d{2}\/\d{4})\s(\d{2}:\d{2})$/', $line, $m)) {
                // Simpan pesan sebelumnya ke array jika ada
                if ($buffer) $messages[] = $buffer;

                // Buat buffer baru
                $buffer = [
                    'ts' => Carbon::parse($m[2] . ' ' . $m[3]),
                    'sender' => trim($m[1]),
                    'text' => '', // Teks akan diisi di loop selanjutnya (baris bawahnya)
                    'platform' => 'custom'
                ];
            }

            // --- B. DETEKSI WHATSAPP STANDAR ---
            // Pola: 6/29/25, 7:22 PM - Name: Msg
            elseif (preg_match('/^(\d{1,2}[\/\.]\d{1,2}[\/\.]\d{2,4}),?\s+(\d{1,2}[:\.]\d{2}(?:\s?[APap][Mm])?)\s*-\s*(.*?):\s*(.*)$/', $line, $m)) {
                if ($buffer) $messages[] = $buffer;
                
                // Normalisasi Waktu (ganti titik jadi titik dua)
                $timeStr = str_replace('.', ':', $m[2]);
                $dateStr = str_replace('.', '/', $m[1]);

                try {
                    $buffer = [
                        'ts' => Carbon::parse("$dateStr $timeStr"),
                        'sender' => trim($m[3]),
                        'text' => trim($m[4]),
                        'platform' => 'whatsapp'
                    ];
                } catch (\Exception $e) { $buffer = null; }
            }

            // --- C. DETEKSI WHATSAPP BRACKET ---
            // Pola: [21/06/24 19.22] Name: Msg
            elseif (preg_match('/^\[(\d{1,2}[\/\.]\d{1,2}[\/\.]\d{2,4}),?\s+(\d{1,2}[:\.]\d{2}(?::\d{2})?)\]\s*(.*?):\s*(.*)$/', $line, $m)) {
                if ($buffer) $messages[] = $buffer;

                $timeStr = str_replace('.', ':', $m[2]);
                $dateStr = str_replace('.', '/', $m[1]);

                try {
                    $buffer = [
                        'ts' => Carbon::parse("$dateStr $timeStr"),
                        'sender' => trim($m[3]),
                        'text' => trim($m[4]),
                        'platform' => 'whatsapp'
                    ];
                } catch (\Exception $e) { $buffer = null; }
            }

            // --- D. ISI KONTEN PESAN (Multi-line / Sambungan) ---
            elseif ($buffer) {
                // Filter pesan sistem (Optional)
                if (str_contains($line, 'sticker omitted') || str_contains($line, 'Media tidak disertakan')) {
                    $line = "<Sticker>";
                } elseif (str_contains($line, 'image omitted')) {
                    $line = "<Image>";
                }

                // Gabungkan dengan teks di buffer
                // Jika buffer teks masih kosong (format custom), jangan tambah spasi di awal
                if (empty($buffer['text'])) {
                    $buffer['text'] = $line;
                } else {
                    $buffer['text'] .= " " . $line;
                }
            }
        }

        // Jangan lupa masukkan pesan terakhir yang tersisa di buffer
        if ($buffer) $messages[] = $buffer;

        return $messages;
    }

    /**
     * Penanganan File JSON (Telegram / Discord)
     */
    private function parseJson($path)
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        $messages = [];

        // TELEGRAM EXPORT DETECTOR
        if (isset($data['name']) && isset($data['messages'])) {
            foreach ($data['messages'] as $msg) {
                if ($msg['type'] !== 'message') continue; 

                $text = '';
                if (is_array($msg['text'])) {
                    foreach ($msg['text'] as $part) {
                        $text .= is_array($part) ? ($part['text'] ?? '') : $part;
                    }
                } else {
                    $text = $msg['text'];
                }

                $sender = $msg['from'] ?? $msg['actor'] ?? 'Unknown';

                if (empty($text) && isset($msg['media_type'])) {
                    $text = "<Media: {$msg['media_type']}>"; 
                }

                try {
                    $messages[] = [
                        'ts' => Carbon::parse($msg['date']),
                        'sender' => $sender,
                        'text' => (string) $text,
                        'platform' => 'telegram'
                    ];
                } catch (\Exception $e) { continue; }
            }
        }
        
        // DISCORD EXPORT DETECTOR
        elseif (isset($data[0]['author']) && isset($data[0]['content'])) {
            foreach ($data as $msg) {
                $messages[] = [
                    'ts' => Carbon::parse($msg['timestamp']),
                    'sender' => $msg['author']['name'] ?? 'Unknown',
                    'text' => $msg['content'],
                    'platform' => 'discord'
                ];
            }
        }

        return $messages;
    }

    private function cleanLine($line)
    {
        $line = trim($line);
        // Hapus Invisible Characters & Control Chars
        $line = preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $line);
        $line = str_replace(["\u{202f}", "\u{00a0}", "\u{200e}", "\u{200f}"], ' ', $line);
        return $line;
    }
}