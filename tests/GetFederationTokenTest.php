<?php

namespace Freyo\Flysystem\QcloudCOSv5\Tests;

use Freyo\Flysystem\QcloudCOSv5\Adapter;
use Freyo\Flysystem\QcloudCOSv5\Plugins\GetFederationToken;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Qcloud\Cos\Client;

class GetFederationTokenTest extends TestCase
{
    public function Provider()
    {
        $config = [
            'region'      => getenv('COSV5_REGION'),
            'credentials' => [
                'appId'     => getenv('COSV5_APP_ID'),
                'secretId'  => getenv('COSV5_SECRET_ID'),
                'secretKey' => getenv('COSV5_SECRET_KEY'),
            ],
            'timeout'         => getenv('COSV5_TIMEOUT'),
            'connect_timeout' => getenv('COSV5_CONNECT_TIMEOUT'),
            'bucket'          => getenv('COSV5_BUCKET'),
            'cdn'             => getenv('COSV5_CDN'),
            'scheme'          => getenv('COSV5_SCHEME'),
            'read_from_cdn'   => getenv('COSV5_READ_FROM_CDN'),
        ];

        $client = new Client($config);

        $adapter = new Adapter($client, $config);

        $filesystem = new Filesystem($adapter, $config);

        $filesystem->addPlugin(new GetFederationToken());

        return [
            [$filesystem],
        ];
    }

    /**
     * @dataProvider Provider
     */
    public function testDefault(Filesystem $filesystem)
    {
        $this->assertArrayHasKey('credentials', $filesystem->getFederationToken());
    }

    /**
     * @dataProvider Provider
     */
    public function testCustom(Filesystem $filesystem)
    {
        $this->assertArrayHasKey(
            'credentials',
            $filesystem->getFederationToken('custom/path/to', 7200, function ($path, $config) {
                $appId = $config->get('credentials')['appId'];
                $region = $config->get('region');
                $bucket = $config->get('bucket');

                return [
                    'version'   => '2.0',
                    'statement' => [
                        'action' => [
                            'name/cos:PutObject',
                        ],
                        'effect'    => 'allow',
                        'principal' => ['qcs' => ['*']],
                        'resource'  => [
                            "qcs::cos:$region:uid/$appId:prefix//$appId/$bucket/$path",
                        ],
                    ],
                ];
            })
        );
    }
}
