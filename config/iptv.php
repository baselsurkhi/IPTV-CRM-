<?php

return [

    'player_api_base_url' => rtrim(env(
        'IPTV_PLAYER_API_URL',
        'https://cf.8kplayer-sh12.com/player_api.php'
    ), '?'),

    /** لغة رسائل الـ API عند غياب Accept-Language أو ?locale */
    'api_fallback_locale' => env('IPTV_API_FALLBACK_LOCALE', 'ar'),

    'device_app_key' => env('DEVICE_APP_KEY'),

    'require_app_key' => (bool) env('IPTV_REQUIRE_APP_KEY', false),

    /** يجب مطابقة IP آخر ظهور عند كل تسجيل دخول (افتراضي: معطل لأن الـ DHCP يغيّر الآي بي) */
    'strict_ip_login' => (bool) env('IPTV_STRICT_IP_LOGIN', false),

    'rate_limit_register_per_minute' => (int) env('IPTV_RATE_REGISTER', 15),

    'rate_limit_session_per_minute' => (int) env('IPTV_RATE_SESSION', 40),

    'single_active_token' => (bool) env('IPTV_SINGLE_DEVICE_TOKEN', true),

    'failed_login_before_lockout' => (int) env('IPTV_LOCKOUT_AFTER_FAILS', 8),

    'lockout_duration_minutes' => (int) env('IPTV_LOCKOUT_MINUTES', 30),
];
