<?php
/**
 * This file is part of laravel-oss.
 *
 * Created by HyanCat.
 *
 * Copyright (C) HyanCat. All rights reserved.
 */

return [
    'driver'     => 'oss',
    'access_id'  => env('OSS_ACCESS_ID'),
    'access_key' => env('OSS_ACCESS_KEY'),
    // endpoint: https://oss-cn-beijing.aliyuncs.com
    'endpoint'   => env('OSS_INTERNAL', false) ? env('OSS_ENDPOINT_INTERNAL') : env('OSS_ENDPOINT'),
    // the cname host
    'cname'      => env('OSS_CNAME'),
    'bucket'     => env('OSS_BUCKET'),

    'object_prefix' => env('OSS_OBJECT_PREFIX', ''),
];
