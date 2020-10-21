<?php
/**
 * This file is part of laravel-oss.
 *
 * Created by HyanCat.
 *
 * Copyright (C) HyanCat. All rights reserved.
 */

namespace Ruogoo\Oss;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

class OssServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/oss.php', 'filesystems.disks.oss');

        $this->extendOssDriver();
    }

    protected function extendOssDriver(): void
    {
        Storage::extend('oss', function ($app, $config) {
            $ossConfig = $app['config']["filesystems.disks.oss"];
            $adapter = $this->makeAdapter($ossConfig);
            return new Filesystem($adapter);
        });
    }

    protected function makeAdapter($config): OssServiceAdapter
    {
        $accessId = $config['access_id'];
        $accessKey = $config['access_key'];
        $endPoint = $config['endpoint'];
        $bucket = $config['bucket'];
        $cname = $config['cname'];

        $prefix = Arr::get($config, 'object_prefix');

        $client = new OssClient($accessId, $accessKey, $endPoint);
        $adapter = new OssServiceAdapter($client, $bucket, $prefix, $cname);

        return $adapter;
    }
}
