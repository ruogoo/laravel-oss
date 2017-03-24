<?php
/**
 * This file is part of laravel-oss.
 *
 * Created by HyanCat.
 *
 * Copyright (C) HyanCat. All rights reserved.
 */

namespace HyanCat\Oss;

use Illuminate\Support\Facades\Facade;

/**
 * Class OssFacade
 * @namespace HyanCat\Oss
 * @see \HyanCat\Oss\OssServiceProvider
 */
class OssFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'hyancat.oss';
    }
}
