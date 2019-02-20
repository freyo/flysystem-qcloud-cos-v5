<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class TCaptcha extends AbstractPlugin
{
    const TICKET_VERIFY = 'https://ssl.captcha.qq.com/ticket/verify';

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
        return 'tcaptcha';
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
     *
     * @return mixed
     */
    public function verify($ticket, $randStr, $userIP)
    {
        $contents = $this->filesystem
            ->getAdapter()
            ->getHttpClient()
            ->get(self::TICKET_VERIFY, ['query' => [
                'aid'          => $this->aid,
                'AppSecretKey' => $this->appSecretKey,
                'Ticket'       => $ticket,
                'Randstr'      => $randStr,
                'UserIP'       => $userIP,
            ]])
            ->getBody()
            ->getContents();

        return \GuzzleHttp\json_decode($contents);
    }
}
