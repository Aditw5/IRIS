<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class WhatsAppHelper
{
    public static function send($nomor, $pesan)
    {
        return Http::withOptions([
            'timeout' => config('whatsapp.timeout'),
            'connect_timeout' => config('whatsapp.connect_timeout'),
        ])->get(config('whatsapp.url'), [
            'token' => config('whatsapp.token') . '.' . config('whatsapp.secret_key'),
            'phone' => $nomor,
            'message' => $pesan,
        ]);
    }

    public static function sendQueryString($nomor, $pesan)
    {
        $url = config('whatsapp.url')
            . '?token=' . config('whatsapp.token') . '.' . config('whatsapp.secret_key')
            . '&phone=' . $nomor
            . '&message=' . urlencode($pesan);

        return Http::get($url);
    }
}
