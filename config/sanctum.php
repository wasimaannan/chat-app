<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf('%s%s', 'localhost,127.0.0.1,127.0.0.1:8000', env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''))),
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
];
