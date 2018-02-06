<?php

namespace Freyo\Flysystem\QcloudCOSv5;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\NoSuchKeyException;

/**
 * Class Adapter.
 */
class Adapter extends AbstractAdapter
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $regionMap = [
        'cn-east'      => 'ap-shanghai',
        'cn-sorth'     => 'ap-guangzhou',
        'cn-north'     => 'ap-beijing-1',
        'cn-south-2'   => 'ap-guangzhou-2',
        'cn-southwest' => 'ap-chengdu',
        'sg'           => 'ap-singapore',
        'tj'           => 'ap-beijing-1',
        'bj'           => 'ap-beijing',
        'sh'           => 'ap-shanghai',
        'gz'           => 'ap-guangzhou',
        'cd'           => 'ap-chengdu',
        'sgp'          => 'ap-singapore',
    ];

    /**
     * Adapter constructor.
     *
     * @param Client $client
     * @param array  $config
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;

        $this->setPathPrefix($config['cdn']);
    }

    /**
     * @return string
     */
    public function getBucket()
    {
        return $this->config['bucket'];
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->config['credentials']['appId'];
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->regionMap[$this->config['region']];
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function getSourcePath($path)
    {
        return sprintf('%s-%s.cos.%s.myqcloud.com/%s',
            $this->getBucket(), $this->getAppId(), $this->getRegion(), $path
        );
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getUrl($path)
    {
        if (!empty($this->config['cdn'])) {
            return $this->applyPathPrefix($path);
        }

        return urldecode(
            $this->client->getObjectUrl($this->getBucket(), $path)
        );
    }

    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return array|bool
     */
    public function write($path, $contents, Config $config)
    {
        return $this->client->upload($this->getBucket(), $path, $contents);
    }

    /**
     * @param string   $path
     * @param resource $resource
     * @param Config   $config
     *
     * @return array|bool
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->client->upload($this->getBucket(), $path, stream_get_contents($resource, -1, 0));
    }

    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return array|bool
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * @param string   $path
     * @param resource $resource
     * @param Config   $config
     *
     * @return array|bool
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        $source = $this->getSourcePath($path);

        $response = $this->client->copyObject([
            'Bucket'     => $this->getBucket(),
            'Key'        => $newpath,
            'CopySource' => $source,
        ]);

        $this->delete($path);

        return (bool) $response;
    }

    /**
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $source = $this->getSourcePath($path);

        return (bool) $this->client->copyObject([
            'Bucket'     => $this->getBucket(),
            'Key'        => $newpath,
            'CopySource' => $source,
        ]);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        return (bool) $this->client->deleteObject([
            'Bucket' => $this->getBucket(),
            'Key'    => $path,
        ]);
    }

    /**
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $model = $this->listContents($dirname);

        $keys = array_map(function ($item) {
            return ['Key' => $item['Key']];
        }, (array) $model->get('Contents'));

        return (bool) $this->client->deleteObjects([
            'Bucket'  => $this->getBucket(),
            'Objects' => $keys,
        ]);
    }

    /**
     * @param string $dirname
     * @param Config $config
     *
     * @return array|bool
     */
    public function createDir($dirname, Config $config)
    {
        return $this->client->putObject([
            'Bucket' => $this->getBucket(),
            'Key'    => $dirname . '/_blank',
            'Body'   => '',
        ]);
    }

    /**
     * @param string $path
     * @param string $visibility
     *
     * @return bool
     */
    public function setVisibility($path, $visibility)
    {
        $visibility = ($visibility === AdapterInterface::VISIBILITY_PUBLIC)
            ? 'public-read' : 'private';

        return (bool) $this->client->PutObjectAcl([
            'Bucket' => $this->getBucket(),
            'Key'    => $path,
            'ACL'    => $visibility,
        ]);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function has($path)
    {
        try {
            return (bool) $this->getMetadata($path);
        } catch (NoSuchKeyException $e) {
            return false;
        }
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function read($path)
    {
        try {
            $response = $this->client->getObject([
                'Bucket' => $this->getBucket(),
                'Key'    => $path,
            ]);

            return ['contents' => (string) $response->get('Body')];
        } catch (NoSuchKeyException $e) {
            return false;
        }
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function readStream($path)
    {
        try {
            return ['stream' => fopen($this->getUrl($path), 'r')];
        } catch (NoSuchKeyException $e) {
            return false;
        }
    }

    /**
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array|bool
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->client->listObjects([
            'Bucket'    => $this->getBucket(),
            'Prefix'    => $directory . '/',
            'Delimiter' => $recursive ? '' : '/',
        ]);
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getMetadata($path)
    {
        return $this->client->headObject([
            'Bucket' => $this->getBucket(),
            'Key'    => $path,
        ]);
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getSize($path)
    {
        $meta = $this->getMetadata($path);

        return $meta->hasKey('ContentLength')
            ? ['size' => $meta->get('ContentLength')] : false;
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getMimetype($path)
    {
        $meta = $this->getMetadata($path);

        return $meta->hasKey('ContentType')
            ? ['mimetype' => $meta->get('ContentType')] : false;
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getTimestamp($path)
    {
        $meta = $this->getMetadata($path);

        return $meta->hasKey('LastModified')
            ? ['timestamp' => strtotime($meta->get('LastModified'))] : false;
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getVisibility($path)
    {
        $meta = $this->client->getObjectAcl([
            'Bucket' => $this->getBucket(),
            'Key'    => $path,
        ]);

        foreach ($meta->get('Grants') as $grant) {
            if (isset($grant['Grantee']['URI'])
                && $grant['Permission'] === 'READ'
                && strpos($grant['Grantee']['URI'], 'global/AllUsers') !== false
            ) {
                return ['visibility' => AdapterInterface::VISIBILITY_PUBLIC];
            }
        }

        return ['visibility' => AdapterInterface::VISIBILITY_PRIVATE];
    }
}
