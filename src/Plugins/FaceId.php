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
     * @param $ruleId
     * @param array $options
     *
     * @return array|bool
     */
    public function detectAuth($ruleId, array $options = [])
    {
        $params = array_merge($options, [
            'RuleId' => $ruleId,
        ]);

        return $this->request($params, 'DetectAuth', 'faceid');
    }

    /**
     * @param $ruleId
     * @param $bizToken
     * @param int $infoType
     *
     * @return array|bool
     */
    public function getDetectInfo($ruleId, $bizToken, $infoType = 0)
    {
        $params = [
            'RuleId' => $ruleId,
            'BizToken' => $bizToken,
            'InfoType' => $infoType,
        ];

        return $this->request($params, 'GetDetectInfo', 'faceid');
    }
}