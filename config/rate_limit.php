<?php

// Kuota per jendela waktu. Hidup/mati tiap kebijakan diatur di menu Setting Sistem.
return [
    'ip' => ['requests' => (int) env('RATE_LIMIT_IP_REQUESTS', 600), 'seconds' => (int) env('RATE_LIMIT_IP_SECONDS', 60)],
    'user' => ['requests' => (int) env('RATE_LIMIT_USER_REQUESTS', 300), 'seconds' => (int) env('RATE_LIMIT_USER_SECONDS', 60)],
    'login' => ['requests' => (int) env('RATE_LIMIT_LOGIN_REQUESTS', 10), 'seconds' => (int) env('RATE_LIMIT_LOGIN_SECONDS', 60)],
];
