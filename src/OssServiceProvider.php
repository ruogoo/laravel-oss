<?php
/**
 * This file is part of laravel-oss.
 *
 * Created by HyanCat.
 *
 * Copyright (C) HyanCat. All rights reserved.
 */

namespace HyanCat\Oss;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

class OssServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton('hyancat.oss', function ($app) {
            $config = $app['config']["filesystems.disks.oss"];
            return $this->makeAdapter($config);
        });

        $this->extendOssDriver();
    }

    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/oss.php', 'filesystems.disks.oss');
    }

    public function provides()
    {
        return ['hyancat.oss'];
    }

    protected function makeAdapter($config)
    {
        $accessId  = $config['access_id'];
        $accessKey = $config['access_key'];
        $endPoint  = $config['endpoint'];
        $bucket    = $config['bucket'];

        $prefix = null;
        if (isset($config['object_prefix'])) {
            $prefix = $config['object_prefix'];
        }

        $client  = new OssClient($accessId, $accessKey, $endPoint);
        $adapter = new OssServiceAdapter($client, $bucket, $prefix);

        return $adapter;
    }

    protected function extendOssDriver()
    {
        Storage::extend('oss', function ($app, $config) {
            return new Filesystem($app['hyancat.oss']);
        });
    }
}
