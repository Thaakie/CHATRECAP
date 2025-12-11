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
            // Default TXT (WhatsApp / Line)
            return $this->parseTxt($path);
        }
    }

    /**
     * Penanganan File Text (WhatsApp / Line)
     */
    private function parseTxt($path)
    {
        $content = file_get_contents($path);
        
        // AUTO-FIX ENCODING (Membersihkan karakter aneh)
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $messages = [];

        foreach ($lines as $line) {
            $line = $this->cleanLine($line);
            if (empty($line)) continue;

            // DETEKSI OTOMATIS: Apakah ini WhatsApp?
            // Pola: Tanggal, Jam - Nama: Pesan
            if (preg_match('/^(\d{1,2}[\/\.]\d{1,2}[\/\.]\d{2,4}),?\s+(\d{1,2}[:\.]\d{2}(?:\s?[APap][Mm])?)\s*-\s*(.*?):\s*(.*)$/', $line, $m) ||
                preg_match('/^\[(\d{1,2}[\/\.]\d{1,2}[\/\.]\d{2,4}),?\s+(\d{1,2}[:\.]\d{2}(?::\d{2})?)\]\s*(.*?):\s*(.*)$/', $line, $m)) {
                
                $dateRaw = str_replace('.', '/', $m[1]);
                $timeRaw = str_replace('.', ':', $m[2]);
                $sender  = trim($m[3]);
                $text    = trim($m[4]);

                try {
                    $dt = Carbon::parse("$dateRaw $timeRaw");
                    $messages[] = [
                        'ts' => $dt,
                        'sender' => $sender,
                        'text' => $text,
                        'platform' => 'whatsapp'
                    ];
                } catch (\Exception $e) { continue; }
            }
            
            // DETEKSI OTOMATIS: Apakah ini LINE? (Biasanya format: Jam [TAB] Nama [TAB] Pesan)
            // (Bisa ditambahkan nanti)
        }

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
                if ($msg['type'] !== 'message') continue; // Skip service message

                // Handle Text (Telegram text bisa berupa array jika ada link)
                $text = '';
                if (is_array($msg['text'])) {
                    foreach ($msg['text'] as $part) {
                        $text .= is_array($part) ? ($part['text'] ?? '') : $part;
                    }
                } else {
                    $text = $msg['text'];
                }

                // Handle Sender (Kadang ada 'from', kadang 'actor')
                $sender = $msg['from'] ?? $msg['actor'] ?? 'Unknown';

                if (empty($text) && isset($msg['media_type'])) {
                    $text = "<Media: {$msg['media_type']}>"; // Penanda media
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
        
        // DISCORD EXPORT DETECTOR (Format umum bot)
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