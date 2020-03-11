<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins\Traits;

use Carbon\Carbon;

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
     * @param array           $args
     * @param string          $action
     * @param string          $service
     * @param string          $version
     * @param string|int|null $timestamp
     *
     * @return bool|array
     */
    protected function request(array $args, $action, $service, $version, $timestamp = null)
    {
        $client = $this->getHttpClient($service);

        $response = $client->post('/', [
            'body' => $body = \GuzzleHttp\json_encode(
                $args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            'headers' => [
                'X-TC-Action'    => $action,
                'X-TC-Region'    => $this->getConfig()->get('region'),
                'X-TC-Timestamp' => $timestamp = $timestamp ?: time(),
                'X-TC-Version'   => $version,
                'Authorization'  => $this->getAuthorization($timestamp, $service, $body),
                'Content-Type'   => 'application/json',
            ],
        ]);

        $contents = $response->getBody()->getContents();

        return $this->normalize($contents);
    }

    /**
     * @param $service
     *
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
     * @param string|int|null $timestamp
     * @param string          $service
     * @param string          $body
     *
     * @return string
     */
    protected function getAuthorization($timestamp, $service, $body)
    {
        return sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            'TC3-HMAC-SHA256',
            $this->getCredentials()['secretId'],
            Carbon::createFromTimestampUTC($timestamp)->toDateString()."/{$service}/tc3_request",
            'content-type;host',
            hash_hmac(
                'SHA256',
                $this->getSignatureString($timestamp, $service, $body),
                $this->getRequestKey($timestamp, $service)
            )
        );
    }

    /**
     * @param string|int|null $timestamp
     * @param string          $service
     *
     * @return string
     */
    protected function getRequestKey($timestamp, $service)
    {
        $secretDate = hash_hmac(
            'SHA256',
            Carbon::createFromTimestampUTC($timestamp)->toDateString(),
            'TC3'.$this->getCredentials()['secretKey'],
            true
        );
        $secretService = hash_hmac('SHA256', $service, $secretDate, true);

        return hash_hmac('SHA256', 'tc3_request', $secretService, true);
    }

    /**
     * @param string $service
     * @param string $body
     *
     * @return string
     */
    protected function getCanonicalRequest($service, $body)
    {
        return implode("\n", [
            'POST',
            '/',
            '',
            'content-type:application/json',
            "host:{$service}.tencentcloudapi.com",
            '',
            'content-type;host',
            hash('SHA256', $body),
        ]);
    }

    /**
     * @param string|int|null $timestamp
     * @param string          $service
     * @param string          $body
     *
     * @return string
     */
    protected function getSignatureString($timestamp, $service, $body)
    {
        return implode("\n", [
            'TC3-HMAC-SHA256',
            $timestamp,
            Carbon::createFromTimestampUTC($timestamp)->toDateString()."/{$service}/tc3_request",
            hash('SHA256', $this->getCanonicalRequest($service, $body)),
        ]);
    }
}
