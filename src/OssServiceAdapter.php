<?php
/**
 * This file is part of laravel-oss.
 *
 * Created by HyanCat.
 *
 * Copyright (C) HyanCat. All rights reserved.
 */

namespace HyanCat\Oss;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use OSS\Core\OssException;
use OSS\OssClient;

class OssServiceAdapter extends AbstractAdapter
{
    use StreamedTrait;
    use NotSupportingVisibilityTrait;

    /**
     * Aliyun Oss Client.
     *
     * @var \OSS\OssClient
     */
    protected $client;

    /**
     * bucket name.
     *
     * @var string
     */
    protected $bucket;

    /**
     * cname host
     *
     * @var ?string
     */
    protected $cname;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected static $mappingOptions = [
        'mimetype' => OssClient::OSS_CONTENT_TYPE,
        'size'     => OssClient::OSS_LENGTH,
    ];

    /**
     * Constructor.
     *
     * @param OssClient $client
     * @param string    $bucket
     * @param string    $prefix
     * @param array     $options
     */
    public function __construct(OssClient $client, $bucket, $prefix = null, $cname = null, array $options = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->cname = $cname;
        $this->setPathPrefix($prefix);
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get the Aliyun Oss Client bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get the Aliyun Oss Client instance.
     *
     * @return \OSS\OssClient
     */
    public function getClient()
    {
        return $this->client;
    }

    public function getUrl($path)
    {
        $object = $this->applyPathPrefix($path);

        $meta = $this->client->getObjectMeta($this->bucket, $object);
        $url = $meta['oss-request-url'];

        if ($this->cname) {
            $host = parse_url($url, PHP_URL_HOST);
            $cnamedUrl = str_replace_first($host, $this->cname, $url);
            return $cnamedUrl;
        }

        return $url;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $object  = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);

        if (! isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = Util::contentSize($contents);
        }

        if (! isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $contents);
        }

        try {
            $this->client->putObject($this->bucket, $object, $contents, $options);
        } catch (OssException $e) {
            return false;
        }

        $type               = 'file';
        $result             = compact('type', 'path', 'contents');
        $result['mimetype'] = $options[OssClient::OSS_CONTENT_TYPE];
        $result['size']     = $options[OssClient::OSS_LENGTH];

        return $result;
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newPath
     * @return bool
     */
    public function rename($path, $newPath)
    {
        if (! $this->copy($path, $newPath)) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newPath
     * @return bool
     */
    public function copy($path, $newPath)
    {
        $object    = $this->applyPathPrefix($path);
        $newObject = $this->applyPathPrefix($newPath);

        try {
            $this->client->copyObject($this->bucket, $object, $this->bucket, $newObject);
        } catch (OssException $e) {
            return false;
        }

        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     * @return bool
     */
    public function delete($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $this->client->deleteObject($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirName
     * @return bool
     */
    public function deleteDir($dirName)
    {
        $list = $this->listContents($dirName, true);

        $objects = [];
        foreach ($list as $val) {
            if ($val['type'] === 'file') {
                $objects[] = $this->applyPathPrefix($val['path']);
            } else {
                $objects[] = $this->applyPathPrefix($val['path']) . '/';
            }
        }

        try {
            $this->client->deleteObjects($this->bucket, $objects);
        } catch (OssException $e) {
            return false;
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirName directory name
     * @param Config $config
     * @return array|false
     */
    public function createDir($dirName, Config $config)
    {
        $object  = $this->applyPathPrefix($dirName);
        $options = $this->getOptionsFromConfig($config);

        try {
            $this->client->createObjectDir($this->bucket, $object, $options);
        } catch (OssException $e) {
            return false;
        }

        return ['path' => $dirName, 'type' => 'dir'];
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     * @return bool
     */
    public function has($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $exists = $this->client->doesObjectExist($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return $exists;
    }

    /**
     * Read a file.
     *
     * @param string $path
     * @return array|false
     */
    public function read($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $contents = $this->client->getObject($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return compact('contents', 'path');
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $directory = $this->applyPathPrefix($directory);

        $bucket     = $this->bucket;
        $delimiter  = '/';
        $nextMarker = '';
        $maxKeys    = 1000;
        $options    = [
            'delimiter' => $delimiter,
            'prefix'    => $directory,
            'max-keys'  => $maxKeys,
            'marker'    => $nextMarker,
        ];

        $listObjectInfo = $this->client->listObjects($bucket, $options);

        $objectList = $listObjectInfo->getObjectList(); // 文件列表
        $prefixList = $listObjectInfo->getPrefixList(); // 目录列表

        $result = [];
        foreach ($objectList as $objectInfo) {
            if ($objectInfo->getSize() === 0 && $directory === $objectInfo->getKey()) {
                $result[] = [
                    'type'      => 'dir',
                    'path'      => $this->removePathPrefix(rtrim($objectInfo->getKey(), '/')),
                    'timestamp' => strtotime($objectInfo->getLastModified()),
                ];
                continue;
            }

            $result[] = [
                'type'      => 'file',
                'path'      => $this->removePathPrefix($objectInfo->getKey()),
                'timestamp' => strtotime($objectInfo->getLastModified()),
                'size'      => $objectInfo->getSize(),
            ];
        }

        foreach ($prefixList as $prefixInfo) {
            if ($recursive) {
                $next   = $this->listContents($this->removePathPrefix($prefixInfo->getPrefix()), $recursive);
                $result = array_merge($result, $next);
            } else {
                $result[] = [
                    'type'      => 'dir',
                    'path'      => $this->removePathPrefix(rtrim($prefixInfo->getPrefix(), '/')),
                    'timestamp' => 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     * @return array|false
     * @throws \OSS\Core\OssException
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $result = $this->client->getObjectMeta($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return [
            'type'      => 'file',
            'dirname'   => Util::dirname($path),
            'path'      => $path,
            'timestamp' => strtotime($result['last-modified']),
            'mimetype'  => $result['content-type'],
            'size'      => $result['content-length'],
        ];
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     * @return array|false
     */
    public function getSize($path)
    {
        $meta = $this->getMetadata($path);

        return $meta['size'];
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     * @return array|false
     */
    public function getMimetype($path)
    {
        $meta = $this->getMetadata($path);

        return $meta['mimetype'];
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     * @return array|false
     * @throws \OSS\Core\OssException
     */
    public function getTimestamp($path)
    {
        $meta = $this->getMetadata($path);

        return $meta['timestamp'];
    }

    /**
     * Get the signed download url of a file.
     *
     * @param string $path
     * @param int    $expires
     * @param string $host_name
     * @param bool   $use_ssl
     * @return string
     */
    public function getSignedDownloadUrl($path, $expires = 3600, $host_name = '', $use_ssl = false)
    {
        $object = $this->applyPathPrefix($path);
        $url    = $this->client->signUrl($this->bucket, $object, $expires);

        if (! empty($host_name) || $use_ssl) {
            $parse_url = parse_url($url);
            if (! empty($host_name)) {
                $parse_url['host'] = $this->bucket . '.' . $host_name;
            }
            if ($use_ssl) {
                $parse_url['scheme'] = 'https';
            }

            $url = (isset($parse_url['scheme']) ? $parse_url['scheme'] . '://' : '') . (isset($parse_url['user']) ? $parse_url['user'] . (isset($parse_url['pass']) ? ':' . $parse_url['pass'] : '') . '@' : '') . (isset($parse_url['host']) ? $parse_url['host'] : '') . (isset($parse_url['port']) ? ':' . $parse_url['port'] : '') . (isset($parse_url['path']) ? $parse_url['path'] : '') . (isset($parse_url['query']) ? '?' . $parse_url['query'] : '');
        }

        return $url;
    }

    /**
     * Get options from the config.
     *
     * @param Config $config
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = $this->options;
        foreach (static::$mappingOptions as $option => $ossOption) {
            if (! $config->has($option)) {
                continue;
            }
            $options[$ossOption] = $config->get($option);
        }

        return $options;
    }
}
