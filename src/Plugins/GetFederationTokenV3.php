<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use Closure;
use League\Flysystem\Plugin\AbstractPlugin;

/**
 * Class GetFederationToken.
 */
class GetFederationTokenV3 extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getFederationTokenV3';
    }

    /**
     * @see https://cloud.tencent.com/document/product/598/33416
     *
     * @param string $path
     * @param int $seconds
     * @param Closure $customPolicy
     * @param string $name
     *
     * @return bool|array
     */
    public function handle($path = '*', $seconds = 7200, Closure $customPolicy = null, $name = 'cos')
    {
        $policy = !is_null($customPolicy)
            ? $this->getCustomPolicy($customPolicy, $path)
            : $this->getPolicy($path);

        $params = [
            'DurationSeconds' => $seconds,
            'Name' => $name,
            'Policy' => urlencode($policy),
        ];

        return $this->request($params, 'GetFederationToken');
    }

    /**
     * @param Closure $callable
     * @param $path
     *
     * @return string
     */
    protected function getCustomPolicy(Closure $callable, $path)
    {
        $policy = call_user_func($callable, $path, $this->getConfig());

        return \GuzzleHttp\json_encode(
            $policy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @see https://cloud.tencent.com/document/product/436/31923
     *
     * @param $path
     *
     * @return string
     */
    protected function getPolicy($path)
    {
        $appId = $this->getCredentials()['appId'];

        $region = $this->getConfig()->get('region');
        $bucket = $this->getConfig()->get('bucket');

        $policy = [
            'version' => '2.0',
            'statement' => [
                'action' => [
                    // 简单上传
                    'name/cos:PutObject',
                    'name/cos:PostObject',
                    // 分片上传
                    'name/cos:InitiateMultipartUpload',
                    'name/cos:ListParts',
                    'name/cos:UploadPart',
                    'name/cos:CompleteMultipartUpload',
                    'name/cos:AbortMultipartUpload',
                ],
                'effect' => 'allow',
                'resource' => [
                    "qcs::cos:$region:uid/$appId:prefix//$appId/$bucket/$path",
                ],
            ],
        ];

        return \GuzzleHttp\json_encode(
            $policy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

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
     * @param $action
     * @param null $timestamp
     *
     * @return bool|array
     */
    protected function request(array $args, $action, $timestamp = null)
    {
        $client = $this->getHttpClient();

        $response = $client->post('/', [
            'headers' => [
                'X-TC-Action' => $action,
                'X-TC-Region' => $this->getConfig()->get('region'),
                'X-TC-Timestamp' => $timestamp = $timestamp ?: time(),
                'X-TC-Version' => '2018-08-13',
                'Authorization' => $this->getAuthorization($args, $timestamp),
            ],
            'json' => $args,
        ]);

        $contents = $response->getBody()->getContents();

        return $this->normalize($contents);
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        return new \GuzzleHttp\Client([
            'base_uri' => 'https://sts.tencentcloudapi.com',
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
     *
     * @return string
     */
    protected function getAuthorization($args, $timestamp)
    {
        return sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            'TC3-HMAC-SHA256',
            $this->getCredentials()['secretId'],
            date('Y-m-d', $timestamp) . '/sts/tc3_request',
            'content-type;host',
            hash_hmac(
                'SHA256',
                $this->getSignatureString($args, $timestamp),
                $this->getRequestKey($timestamp)
            )
        );
    }

    /**
     * @param $timestamp
     *
     * @return string
     */
    protected function getRequestKey($timestamp)
    {
        return hash_hmac('SHA256', 'tc3_request',
            hash_hmac('SHA256', 'sts',
                hash_hmac('SHA256', date('Y-m-d', $timestamp),
                    'TC3' . $this->getCredentials()['secretKey'], true
                ), true
            ), true
        );
    }

    /**
     * @param $args
     *
     * @return string
     */
    protected function getCanonicalRequest($args)
    {
        return implode("\n", [
            'POST',
            '/',
            '',
            'content-type:application/json',
            'host:sts.tencentcloudapi.com',
            '',
            'content-type;host',
            hash("SHA256", \GuzzleHttp\json_encode(
                $args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ))
        ]);
    }

    /**
     * @param $args
     * @param $timestamp
     *
     * @return string
     */
    protected function getSignatureString($args, $timestamp)
    {
        return implode("\n", [
            'TC3-HMAC-SHA256',
            $timestamp,
            date('Y-m-d', $timestamp) . '/sts/tc3_request',
            hash('SHA256', $this->getCanonicalRequest($args)),
        ]);
    }
}
