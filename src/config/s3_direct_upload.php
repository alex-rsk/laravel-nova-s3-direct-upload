<?php
return [
    'key_id'     => env('AWS_ACCESS_KEY_ID', ''),
    'key_secret' => env('AWS_SECRET_ACCESS_KEY', ''),
    'region'     => env('AWS_DEFAULT_REGION', ''),
    'bucket'     => env('AWS_BUCKET', ''),
    'endpoint'   => env('AWS_ENDPOINT', ''),
    'path_style' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'url_prefix' => env('AWS_PATH', ''),
    'version'    =>  '2006-03-01',
    'debug'      => false,
];