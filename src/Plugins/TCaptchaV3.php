<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use Freyo\Flysystem\QcloudCOSv5\Plugins\Traits\TencentCloudAuthV3;
use League\Flysystem\Plugin\AbstractPlugin;

class TCaptchaV3 extends AbstractPlugin
{
    use TencentCloudAuthV3;

    const RESPONSE_SUCCESS = 1;

    protected $aid;
    protected $appSecretKey;

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'TCaptchaV3';
    }

    /**
     * @param $aid
     * @param $appSecretKey
     *
     * @return $this
     */
    public function handle($aid, $appSecretKey)
    {
        $this->aid = $aid;
        $this->appSecretKey = $appSecretKey;

        return $this;
    }

    /**
     * @param $ticket
     * @param $randStr
     * @param $userIP
     * @param array $options
     *
     * @return bool|array
     */
    public function verify($ticket, $randStr, $userIP, array $options = [])
    {
        $params = array_merge([
            'CaptchaType'  => 9,
            'Ticket'       => $ticket,
            'UserIp'       => $userIP,
            'Randstr'      => $randStr,
            'CaptchaAppId' => $this->aid,
            'AppSecretKey' => $this->appSecretKey,
        ], $options);

        return $this->request(
            $params, 'DescribeCaptchaResult', 'captcha', '2019-07-22'
        );
    }
}
