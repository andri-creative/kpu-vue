<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jumlah Baris Awal (Backlog)
    |--------------------------------------------------------------------------
    |
    | Banyaknya baris terakhir yang dikirim saat terminal pertama kali
    | termuat, sebelum penambahan baris baru mulai dipantau.
    |
    */

    'tail_lines' => (int) env('LOG_VIEWER_TAIL', 300),

    /*
    |--------------------------------------------------------------------------
    | Interval Polling (milidetik)
    |--------------------------------------------------------------------------
    |
    | Seberapa sering frontend meminta baris log baru. Menggunakan request
    | pendek (bukan koneksi SSE yang ditahan lama) agar tidak menahan worker
    | PHP server bawaan artisan serve yang single-worker.
    |
    */

    'refresh_interval_ms' => (int) env('LOG_VIEWER_REFRESH', 1500),

    /*
    |--------------------------------------------------------------------------
    | Sumber Log
    |--------------------------------------------------------------------------
    |
    | Peta nama sumber ke path file log yang boleh dibaca & direset.
    |
    */

    'sources' => [
        'laravel' => storage_path('logs/laravel.log'),
        'browser' => storage_path('logs/browser.log'),
    ],

];
