<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
    'http_proxy' => env('OPENAI_HTTP_PROXY'),
    'timeout' => env('OPENAI_TIMEOUT', 30),
];
