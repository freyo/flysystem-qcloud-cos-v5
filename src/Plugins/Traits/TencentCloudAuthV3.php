<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins\Traits;

trait TencentCloudAuthV3
{
    /**
     * @return \League\Flysystem\Config
     */
    protected function getConfig()
    {
        return $this->filesystem->getConfig();
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return $this->getConfig()->get('credentials');
    }

    /**
     * @param array $args
     * @param string $action
     * @param string $service
     * @param string $version
     * @param string|integer|null $timestamp
     *
     * @return bool|array
     */
    protected function request(array $args, $action, $service, $version, $timestamp = null)
    {
        $client = $this->getHttpClient($service);

        $response = $client->post('/', [
            'headers' => [
                'X-TC-Action' => $action,
                'X-TC-Region' => $this->getConfig()->get('region'),
                'X-TC-Timestamp' => $timestamp = $timestamp ?: time(),
                'X-TC-Version' => $version,
                'Authorization' => $this->getAuthorization($args, $timestamp, $service),
                'Content-Type' => 'application/json',
            ],
            'body' => \GuzzleHttp\json_encode(
                $args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
        ]);

        $contents = $response->getBody()->getContents();

        return $this->normalize($contents);
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient($service)
    {
        return new \GuzzleHttp\Client([
            'base_uri' => "https://{$service}.tencentcloudapi.com",
        ]);
    }

    /**
     * @param string $contents
     *
     * @return bool|array
     */
    protected function normalize($contents)
    {
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['Response'])) {
            return false;
        }

        return $data['Response'];
    }

    /**
     * @param $args
     * @param $timestamp
     * @param $service
     *
     * @return string
     */
    protected function getAuthorization($args, $timestamp, $service)
    {
        return sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            'TC3-HMAC-SHA256',
            $this->getCredentials()['secretId'],
            date('Y-m-d', $timestamp) . "/{$service}/tc3_request",
            'content-type;host',
            hash_hmac(
                'SHA256',
                $this->getSignatureString($args, $timestamp, $service),
                $this->getRequestKey($timestamp, $service)
            )
        );
    }

    /**
     * @param $timestamp
     * @param $service
     *
     * @return string
     */
    protected function getRequestKey($timestamp, $service)
    {
        return hash_hmac('SHA256', 'tc3_request',
            hash_hmac('SHA256', $service,
                hash_hmac('SHA256', date('Y-m-d', $timestamp),
                    'TC3' . $this->getCredentials()['secretKey'], true
                ), true
            ), true
        );
    }

    /**
     * @param $args
     * @param $service
     *
     * @return string
     */
    protected function getCanonicalRequest($args, $service)
    {
        return implode("\n", [
            'POST',
            '/',
            '',
            'content-type:application/json',
            "host:{$service}.tencentcloudapi.com",
            '',
            'content-type;host',
            hash('SHA256', \GuzzleHttp\json_encode(
                $args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )),
        ]);
    }

    /**
     * @param $args
     * @param $timestamp
     * @param $service
     *
     * @return string
     */
    protected function getSignatureString($args, $timestamp, $service)
    {
        return implode("\n", [
            'TC3-HMAC-SHA256',
            $timestamp,
            date('Y-m-d', $timestamp) . "/{$service}/tc3_request",
            hash('SHA256', $this->getCanonicalRequest($args, $service)),
        ]);
    }
}