<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use Closure;
use Freyo\Flysystem\QcloudCOSv5\Plugins\Traits\TencentCloudAuthV3;
use League\Flysystem\Plugin\AbstractPlugin;

/**
 * Class GetFederationToken.
 */
class GetFederationTokenV3 extends AbstractPlugin
{
    use TencentCloudAuthV3;

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
            'DurationSeconds' => $seconds,
            'Name'            => $name,
            'Policy'          => urlencode($policy),
        ];

        return $this->request(
            $params, 'GetFederationToken', 'sts', '2018-08-13'
        );
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
                'effect'   => 'allow',
                'resource' => [
                    "qcs::cos:$region:uid/$appId:$bucket-$appId/$path",
                ],
            ],
        ];

        return \GuzzleHttp\json_encode(
            $policy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
