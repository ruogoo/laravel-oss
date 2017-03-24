<?php
/**
 * This file is part of laravel-oss.
 *
 * Created by HyanCat.
 *
 * Copyright (C) HyanCat. All rights reserved.
 */

return [
    'access_id'  => env('OSS_ACCESS_ID'),
    'access_key' => env('OSS_ACCESS_KEY'),
    'https'      => env('OSS_ENABLE_HTTPS', false),
    'endpoint'   => env('OSS_INTERNAL', false) ? env('OSS_ENDPOINT_INTERNAL') : env('OSS_ENDPOINT'),
    'cname'      => env('OSS_CNAME'),
    'bucket'     => env('OSS_BUCKET'),

    'object_prefix' => env('OSS_OBJECT_PREFIX', ''),
];
