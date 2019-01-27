<?php

namespace Freyo\Flysystem\QcloudCOSv5\Tests;

use Freyo\Flysystem\QcloudCOSv5\Adapter;
use Freyo\Flysystem\QcloudCOSv5\Plugins\CDN;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Qcloud\Cos\Client;

class CDNSignatureTest extends TestCase
{
    public function Provider()
    {
        $config = [
            'region' => getenv('COSV5_REGION'),
            'credentials' => [
                'appId' => getenv('COSV5_APP_ID'),
                'secretId' => getenv('COSV5_SECRET_ID'),
                'secretKey' => getenv('COSV5_SECRET_KEY'),
            ],
            'timeout' => getenv('COSV5_TIMEOUT'),
            'connect_timeout' => getenv('COSV5_CONNECT_TIMEOUT'),
            'bucket' => getenv('COSV5_BUCKET'),
            'cdn' => getenv('COSV5_CDN'),
            'scheme' => getenv('COSV5_SCHEME'),
            'read_from_cdn' => getenv('COSV5_READ_FROM_CDN'),
            'cdn_key' => getenv('COSV5_CDN_KEY'),
        ];

        $client = new Client($config);

        $adapter = new Adapter($client, $config);

        $filesystem = new Filesystem($adapter, $config);

        $filesystem->addPlugin(new CDN());

        return [
            [$filesystem],
        ];
    }

    /**
     * @param Filesystem $filesystem
     */
    public function testSignature(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/1.mp4?sign=8fe5c42d7b0dfa7afabef2a33cd96459&t=5a66b340',
            $filesystem->cdn()->signature('http://www.test.com/1.mp4', null, 1516680000)
        );
    }
}