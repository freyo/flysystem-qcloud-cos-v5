<?php

namespace Freyo\Flysystem\QcloudCOSv5\Tests;

use Carbon\Carbon;
use Freyo\Flysystem\QcloudCOSv5\Adapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;
use Qcloud\Cos\Client;

class AdapterTest extends TestCase
{
    public function Provider()
    {
        $config = [
            'region'          => getenv('COSV5_REGION'),
            'credentials'     => [
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
            'encrypt'         => getenv('COSV5_ENCRYPT'),
        ];

        $cosApi = new Client($config);

        $adapter = new Adapter($cosApi, $config);

        $options = [
            'machineId' => PHP_OS.PHP_VERSION,
        ];

        return [
            [$adapter, $config, $options],
        ];
    }

    /**
     * @dataProvider Provider
     */
    public function testWrite(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue((bool) $adapter->write(
            "foo/{$options['machineId']}/foo.md",
            'content',
            new Config()
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testWriteStream(AdapterInterface $adapter, $config, $options)
    {
        $temp = tmpfile();
        fwrite($temp, 'writing to tempfile');
        $this->assertTrue((bool) $adapter->writeStream(
            "foo/{$options['machineId']}/bar.md",
            $temp,
            new Config()
        ));
        fclose($temp);
    }

    /**
     * @dataProvider Provider
     */
    public function testUpdate(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue((bool) $adapter->update(
            "foo/{$options['machineId']}/bar.md",
            uniqid(),
            new Config()
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testUpdateStream(AdapterInterface $adapter, $config, $options)
    {
        $temp = tmpfile();
        fwrite($temp, 'writing to tempfile');
        $this->assertTrue((bool) $adapter->updateStream(
            "foo/{$options['machineId']}/bar.md",
            $temp,
            new Config()
        ));
        fclose($temp);
    }

    /**
     * @dataProvider Provider
     */
    public function testRename(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->rename(
            "foo/{$options['machineId']}/foo.md",
            "/foo/{$options['machineId']}/rename.md"
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testCopy(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->copy(
            "foo/{$options['machineId']}/bar.md",
            "/foo/{$options['machineId']}/copy.md"
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testDelete(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->delete("foo/{$options['machineId']}/rename.md"));
    }

    /**
     * @dataProvider Provider
     */
    public function testCreateDir(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue((bool) $adapter->createDir(
            "bar/{$options['machineId']}", new Config()
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testDeleteDir(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->deleteDir("bar/{$options['machineId']}"));
    }

    /**
     * @dataProvider Provider
     */
    public function testSetVisibility(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->setVisibility(
            "foo/{$options['machineId']}/copy.md", 'private'
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testHas(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->has("foo/{$options['machineId']}/bar.md"));
    }

    /**
     * @dataProvider Provider
     */
    public function testRead(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'contents',
            $adapter->read("foo/{$options['machineId']}/bar.md")
        );

        $this->assertSame(
            file_get_contents($adapter->getTemporaryUrl(
                "foo/{$options['machineId']}/bar.md", Carbon::now()->addMinutes(5)
            )),
            $adapter->read("foo/{$options['machineId']}/bar.md")['contents']
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetUrl(AdapterInterface $adapter, $config, $options)
    {
        $this->assertContains(
            "foo/{$options['machineId']}/bar.md",
            $adapter->getUrl("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testReadStream(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'stream',
            $adapter->readStream("foo/{$options['machineId']}/bar.md")
        );

        $this->assertSame(
            stream_get_contents(fopen($adapter->getTemporaryUrl(
                "foo/{$options['machineId']}/bar.md", Carbon::now()->addMinutes(5)
            ), 'rb', false)),
            stream_get_contents($adapter->readStream(
                "foo/{$options['machineId']}/bar.md")['stream']
            )
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testListContents(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            0,
            $adapter->listContents("foo/{$options['machineId']}")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetMetadata(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'ContentLength',
            $adapter->getMetadata("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetSize(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'size',
            $adapter->getSize("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetMimetype(AdapterInterface $adapter, $config, $options)
    {
        $this->assertNotSame(
            ['mimetype' => ''],
            $adapter->getMimetype("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetTimestamp(AdapterInterface $adapter, $config, $options)
    {
        $this->assertNotSame(
            ['timestamp' => 0],
            $adapter->getTimestamp("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetVisibility(AdapterInterface $adapter, $config, $options)
    {
        $this->assertSame(
            ['visibility' => 'private'],
            $adapter->getVisibility("foo/{$options['machineId']}/copy.md")
        );
    }
}
