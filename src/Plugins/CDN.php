<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class CDN extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'cdn';
    }

    /**
     * @return $this
     */
    public function handle()
    {
        return $this;
    }

    /**
     * @param $url
     *
     * @return bool
     */
    public function refreshUrl($url)
    {
        $urls = is_array($url) ? $url : func_get_args();

        return $this->request($urls, 'urls', 'RefreshCdnUrl');
    }

    /**
     * @param $dir
     *
     * @return bool
     */
    public function refreshDir($dir)
    {
        $dirs = is_array($dir) ? $dir : func_get_args();

        return $this->request($dirs, 'dirs', 'RefreshCdnDir');
    }

    /**
     * @param array  $args
     * @param string $key
     * @param string $action
     *
     * @return bool
     */
    protected function request(array $args, $key, $action)
    {
        $client = $this->getHttpClient();

        $response = $client->post('/v2/index.php', [
            'form_params' => $this->buildFormParams($args, $key, $action),
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
            'verify'   => false,
            'base_uri' => 'https://cdn.api.qcloud.com',
        ]);
    }

    /**
     * @param array  $values
     * @param string $key
     * @param string $action
     *
     * @return array
     */
    protected function buildFormParams(array $values, $key, $action)
    {
        $keys = array_map(function ($n) use ($key) {
            return sprintf("$key.%d", $n);
        }, range(0, count($values) - 1));

        $params = array_combine($keys, $values);

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
            'Action'    => $action,
            'SecretId'  => $this->getCredentials()['secretId'],
            'Timestamp' => time(),
            'Nonce'     => rand(1, 65535),
        ], $params);
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return $this->filesystem->getConfig()->get('credentials');
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

        $srcStr = 'POSTcdn.api.qcloud.com/v2/index.php?'.urldecode(http_build_query($params));

        return base64_encode(hash_hmac('sha1', $srcStr, $this->getCredentials()['secretKey'], true));
    }

    /**
     * @param string $contents
     *
     * @return bool
     */
    protected function normalize($contents)
    {
        $json = json_decode($contents);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return 0 === $json->code;
    }
}
