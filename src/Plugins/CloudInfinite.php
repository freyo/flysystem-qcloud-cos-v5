<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;
use SimpleXMLElement;

class CloudInfinite extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'cloudInfinite';
    }

    /**
     * @return $this
     */
    public function handle()
    {
        return $this;
    }

    /**
     * @param string $objectKey
     * @param array  $picOperations
     *
     * @return array
     */
    public function imageProcess($objectKey, array $picOperations)
    {
        $adapter = $this->filesystem->getAdapter();

        $url = 'https://'.$adapter->getPicturePath($objectKey).'?image_process';

        $response = $adapter->getHttpClient()->post($url, [
            'http_errors' => false,
            'headers'     => [
                'Authorization'  => $adapter->getAuthorization('POST', $url),
                'Pic-Operations' => \GuzzleHttp\json_encode(
                    $picOperations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ],
        ]);

        return $this->parse(
            $response->getBody()->getContents()
        );
    }

    /**
     * @param string $objectKey
     * @param array  $contentRecognition
     *
     * @return array
     */
    public function contentRecognition($objectKey, array $contentRecognition)
    {
        $adapter = $this->filesystem->getAdapter();

        $url = 'https://'.$adapter->getPicturePath($objectKey).'?CR';

        $response = $adapter->getHttpClient()->get($url, [
            'http_errors' => false,
            'headers'     => [
                'Authorization'       => $adapter->getAuthorization('GET', $url),
                'Content-Recognition' => \GuzzleHttp\json_encode(
                    $contentRecognition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ],
        ]);

        return $this->parse(
            $response->getBody()->getContents()
        );
    }

    /**
     * @param string $xml
     *
     * @return array
     */
    protected function parse($xml)
    {
        $backup = libxml_disable_entity_loader(true);

        $result = $this->normalize(
            simplexml_load_string(
                $this->sanitize($xml),
                'SimpleXMLElement',
                LIBXML_COMPACT | LIBXML_NOCDATA | LIBXML_NOBLANKS
            )
        );

        libxml_disable_entity_loader($backup);

        return $result;
    }

    /**
     * Object to array.
     *
     *
     * @param SimpleXMLElement $obj
     *
     * @return array
     */
    protected function normalize($obj)
    {
        $result = null;

        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $res = $this->normalize($value);
                if (('@attributes' === $key) && ($key)) {
                    $result = $res; // @codeCoverageIgnore
                } else {
                    $result[$key] = $res;
                }
            }
        } else {
            $result = $obj;
        }

        return $result;
    }

    /**
     * Delete invalid characters in XML.
     *
     * @see https://www.w3.org/TR/2008/REC-xml-20081126/#charsets - XML charset range
     * @see http://php.net/manual/en/regexp.reference.escape.php - escape in UTF-8 mode
     *
     * @param string $xml
     *
     * @return string
     */
    protected function sanitize($xml)
    {
        return preg_replace(
            '/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u',
            '',
            $xml
        );
    }
}
