<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use Closure;
use League\Flysystem\Plugin\AbstractPlugin;

/**
 * Class GetFederationToken.
 */
class GetFederationToken extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getFederationToken';
    }

    /**
     * @see https://cloud.tencent.com/document/product/598/13896
     *
     * @param string  $path
     * @param int     $seconds
     * @param Closure $customPolicy
     * @param string  $name
     *
     * @return bool|array
     */
    public function handle($path = '*', $seconds = 7200, Closure $customPolicy = null, $name = 'cos')
    {
        $policy = !is_null($customPolicy)
            ? $this->getCustomPolicy($customPolicy, $path)
            : $this->getDefaultPolicy($path);

        $params = [
            'durationSeconds' => $seconds,
            'name'            => $name,
            'policy'          => urlencode($policy),
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

        return \GuzzleHttp\json_encode($policy, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @see https://cloud.tencent.com/document/product/436/31923
     *
     * @param $path
     *
     * @return string
     */
    protected function getDefaultPolicy($path)
    {
        $appId = $this->getCredentials()['appId'];

        $region = $this->getConfig()->get('region');
        $bucket = $this->getConfig()->get('bucket');

        $policy = [
            'version'   => '2.0',
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
                'effect'    => 'allow',
                'principal' => ['qcs' => ['*']],
                'resource'  => [
                    "qcs::cos:$region:uid/$appId:prefix//$appId/$bucket/$path",
                ],
            ],
        ];

        return \GuzzleHttp\json_encode($policy, JSON_UNESCAPED_SLASHES);
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
     *
     * @return bool|array
     */
    protected function request(array $args, $action)
    {
        $client = $this->getHttpClient();

        $response = $client->post('/v2/index.php', [
            'form_params' => $this->buildFormParams($args, $action),
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
            'base_uri' => 'https://sts.api.qcloud.com',
        ]);
    }

    /**
     * @param array  $params
     * @param string $action
     *
     * @return array
     */
    protected function buildFormParams(array $params, $action)
    {
        $params = $this->addCommonParams($params, $action);

        return $this->addSignature($params);
    }

    /**
     * @param array  $params
     * @param string $action
     *
     * @return array
     */
    protected function addCommonParams(array $params, $action)
    {
        return array_merge([
            'Region'    => $this->getConfig()->get('region'),
            'Action'    => $action,
            'SecretId'  => $this->getCredentials()['secretId'],
            'Timestamp' => time(),
            'Nonce'     => rand(1, 65535),
        ], $params);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function addSignature(array $params)
    {
        $params['Signature'] = $this->getSignature($params);

        return $params;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    protected function getSignature(array $params)
    {
        ksort($params);

        $srcStr = 'POSTsts.api.qcloud.com/v2/index.php?'.urldecode(http_build_query($params));

        return base64_encode(hash_hmac('sha1', $srcStr, $this->getCredentials()['secretKey'], true));
    }

    /**
     * @param string $contents
     *
     * @return bool|array
     */
    protected function normalize($contents)
    {
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || $data['code'] !== 0) {
            return false;
        }

        return $data['data'];
    }
}
