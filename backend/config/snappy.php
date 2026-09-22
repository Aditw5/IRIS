<?php

return [

    'pdf' => [
        'enabled' => true,
        // Lebih aman hardcode jika ENV belum terbaca, pastikan path ini benar!
        'binary'  => env('WKHTML_PDF_BINARY', 'C:\PROGRA~1\wkhtmltopdf\bin\wkhtmltopdf.exe'),
        'timeout' => false,
        'options' => [
            'enable-local-file-access' => true, // ini penting!
            'disable-smart-shrinking' => true,
            // bisa tambah opsi lain di sini
        ],
        'env'     => [],
    ],

    'image' => [
        'enabled' => true,
        'binary'  => env('WKHTML_IMG_BINARY', 'C:\PROGRA~1\wkhtmltopdf\bin\wkhtmltoimage.exe'),
        'timeout' => false,
        'options' => [
            'enable-local-file-access' => true,
        ],
        'env'     => [],
    ],

];
