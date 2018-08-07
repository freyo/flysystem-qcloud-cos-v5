<?php

namespace Freyo\Flysystem\QcloudCOSv5;

use Carbon\Carbon;
use DateTimeInterface;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\NoSuchKeyException;

/**
 * Class Adapter.
 */
class Adapter extends AbstractAdapter implements CanOverwriteFiles
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
        return array_key_exists($this->config['region'], $this->regionMap)
            ? $this->regionMap[$this->config['region']] : $this->config['region'];
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
        if ($this->config['cdn']) {
            return $this->applyPathPrefix($path);
        }

        $objectUrl = $this->client->getObjectUrl(
            $this->getBucket(), $path, null,
                ['Scheme' => isset($this->config['scheme']) ? $this->config['scheme'] : 'http']
        );

        $url = parse_url($objectUrl);

        return sprintf('%s://%s%s', $url['scheme'], $url['host'], urldecode($url['path']));
    }

    /**
     * @param string             $path
     * @param \DateTimeInterface $expiration
     * @param array              $options
     *
     * @return string
     */
    public function getTemporaryUrl($path, DateTimeInterface $expiration, array $options = [])
    {
        $options = array_merge($options,
            ['Scheme' => isset($this->config['scheme']) ? $this->config['scheme'] : 'http']);

        $objectUrl = $this->client->getObjectUrl(
            $this->getBucket(), $path, $expiration->format('c'), $options
        );

        $url = parse_url($objectUrl);

        if ($this->config['cdn']) {
            return $this->config['cdn'].urldecode($url['path']).'?'.$url['query'];
        }

        return sprintf('%s://%s%s?%s', $url['scheme'], $url['host'], urldecode($url['path']), $url['query']);
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
        $result = $this->copy($path, $newpath);

        $this->delete($path);

        return $result;
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

        return (bool) $this->client->copy($this->getBucket(), $newpath, $source);
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
        $response = $this->listObjects($dirname);

        if (!isset($response['Contents'])) {
            return true;
        }

        $keys = array_map(function ($item) {
            return ['Key' => $item['Key']];
        }, (array) $response['Contents']);

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
            'Key'    => $dirname.'/_blank',
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
        switch ($visibility) {
            case AdapterInterface::VISIBILITY_PUBLIC:
                $visibility = 'public-read';
                break;
            default:
                //
        }

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
            if (isset($this->config['read_from_cdn']) && $this->config['read_from_cdn']) {
                $response = $this->getHttpClient()
                                 ->get($this->getTemporaryUrl($path, Carbon::now()->addMinutes(5)))
                                 ->getBody()
                                 ->getContents();
            } else {
                $response = $this->client->getObject([
                    'Bucket' => $this->getBucket(),
                    'Key'    => $path,
                ])->get('Body');
            }

            return ['contents' => (string)$response];
        } catch (NoSuchKeyException $e) {
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        return $client = (new \GuzzleHttp\Client([
            'verify'          => false,
            'timeout'         => $this->config['timeout'],
            'connect_timeout' => $this->config['connect_timeout'],
        ]));
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function readStream($path)
    {
        try {
            $temporaryUrl = $this->getTemporaryUrl($path, Carbon::now()->addMinutes(5));

            $stream = $this->getHttpClient()
                           ->get($temporaryUrl, ['stream' => true])
                           ->getBody()
                           ->detach();

            return ['stream' => $stream];
        } catch (NoSuchKeyException $e) {
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
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
        $list = [];

        $response = $this->listObjects($directory, $recursive);

        foreach ((array) $response->get('Contents') as $content) {
            $list[] = $this->normalizeFileInfo($content);
        }

        return $list;
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
        ])->toArray();
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getSize($path)
    {
        $meta = $this->getMetadata($path);

        return isset($meta['ContentLength'])
            ? ['size' => $meta['ContentLength']] : false;
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getMimetype($path)
    {
        $meta = $this->getMetadata($path);

        return isset($meta['ContentType'])
            ? ['mimetype' => $meta['ContentType']] : false;
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getTimestamp($path)
    {
        $meta = $this->getMetadata($path);

        return isset($meta['LastModified'])
            ? ['timestamp' => strtotime($meta['LastModified'])] : false;
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

    /**
     * @param array $content
     *
     * @return array
     */
    private function normalizeFileInfo(array $content)
    {
        $path = pathinfo($content['Key']);

        return [
            'type'      => 'file',
            'path'      => $content['Key'],
            'timestamp' => Carbon::parse($content['LastModified'])->getTimestamp(),
            'size'      => (int) $content['Size'],
            'dirname'   => (string) $path['dirname'],
            'basename'  => (string) $path['basename'],
            'extension' => isset($path['extension']) ? $path['extension'] : '',
            'filename'  => (string) $path['filename'],
        ];
    }

    /**
     * @param string $directory
     * @param bool   $recursive
     *
     * @return mixed
     */
    private function listObjects($directory = '', $recursive = false)
    {
        return $this->client->listObjects([
            'Bucket'    => $this->getBucket(),
            'Prefix'    => ((string) $directory === '') ? '' : ($directory.'/'),
            'Delimiter' => $recursive ? '' : '/',
        ]);
    }
}
