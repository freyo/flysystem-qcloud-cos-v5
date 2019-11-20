<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use Freyo\Flysystem\QcloudCOSv5\Plugins\Traits\TencentCloudAuthV3;
use League\Flysystem\Plugin\AbstractPlugin;

class FaceId extends AbstractPlugin
{
    use TencentCloudAuthV3;

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'faceId';
    }

    /**
     * @return $this
     */
    public function handle()
    {
        return $this;
    }

    /**
     * @param string $ruleId
     * @param array  $options
     *
     * @return array|bool
     */
    public function detectAuth($ruleId, array $options = [])
    {
        $params = array_merge($options, [
            'RuleId' => (string) $ruleId,
        ]);

        return $this->request(
            $params, 'DetectAuth', 'faceid', '2018-03-01'
        );
    }

    /**
     * @param string $ruleId
     * @param string $bizToken
     * @param string $infoType
     *
     * @return array|bool
     */
    public function getDetectInfo($ruleId, $bizToken, $infoType = '0')
    {
        $params = [
            'RuleId'   => (string) $ruleId,
            'BizToken' => $bizToken,
            'InfoType' => (string) $infoType,
        ];

        return $this->request(
            $params, 'GetDetectInfo', 'faceid', '2018-03-01'
        );
    }
}
