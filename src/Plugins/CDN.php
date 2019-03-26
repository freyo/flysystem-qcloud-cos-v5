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
     * @param string $url
     * @param string $key
     * @param int    $timestamp
     * @param string $signName
     * @param string $timeName
     *
     * @return string
     */
    public function signature($url, $key = null, $timestamp = null, $signName = 'sign', $timeName = 't')
    {
        return $this->signatureD($url, $key, $timestamp, $signName, $timeName);
    }

    /**
     * @param string $url
     * @param string $key
     * @param int    $timestamp
     * @param string $random
     * @param string $signName
     *
     * @return string
     */
    public function signatureA($url, $key = null, $timestamp = null, $random = null, $signName = 'sign')
    {
        $key = $key ?: $this->getConfig()->get('cdn_key');
        $timestamp = $timestamp ?: time();
        $random = $random ?: sha1(uniqid('', true));

        $parsed = parse_url($url);
        $hash = md5(sprintf('%s-%s-%s-%s-%s', $parsed['path'], $timestamp, $random, 0, $key));
        $signature = sprintf('%s-%s-%s-%s', $timestamp, $random, 0, $hash);
        $query = http_build_query([$signName => $signature]);
        $separator = empty($parsed['query']) ? '?' : '&';

        return $url.$separator.$query;
    }

    /**
     * @param string $url
     * @param string $key
     * @param int    $timestamp
     *
     * @return string
     */
    public function signatureB($url, $key = null, $timestamp = null)
    {
        $key = $key ?: $this->getConfig()->get('cdn_key');
        $timestamp = date('YmdHi', $timestamp ?: time());

        $parsed = parse_url($url);
        $hash = md5($key.$timestamp.$parsed['path']);

        return sprintf(
            '%s://%s/%s/%s%s',
            $parsed['scheme'], $parsed['host'], $timestamp, $hash, $parsed['path']
        );
    }

    /**
     * @param string $url
     * @param string $key
     * @param int    $timestamp
     *
     * @return string
     */
    public function signatureC($url, $key = null, $timestamp = null)
    {
        $key = $key ?: $this->getConfig()->get('cdn_key');
        $timestamp = dechex($timestamp ?: time());

        $parsed = parse_url($url);
        $hash = md5($key.$parsed['path'].$timestamp);

        return sprintf(
            '%s://%s/%s/%s%s',
            $parsed['scheme'], $parsed['host'], $hash, $timestamp, $parsed['path']
        );
    }

    /**
     * @param string $url
     * @param string $key
     * @param int    $timestamp
     * @param string $signName
     * @param string $timeName
     *
     * @return string
     */
    public function signatureD($url, $key = null, $timestamp = null, $signName = 'sign', $timeName = 't')
    {
        $key = $key ?: $this->getConfig()->get('cdn_key');
        $timestamp = dechex($timestamp ?: time());

        $parsed = parse_url($url);
        $signature = md5($key.$parsed['path'].$timestamp);
        $query = http_build_query([$signName => $signature, $timeName => $timestamp]);
        $separator = empty($parsed['query']) ? '?' : '&';

        return $url.$separator.$query;
    }

    /**
     * @param $url
     *
     * @return array
     */
    public function pushUrl($url)
    {
        $urls = is_array($url) ? $url : func_get_args();

        return $this->request($urls, 'urls', 'CdnUrlPusher');
    }

    /**
     * @param $url
     *
     * @return array
     */
    public function pushOverseaUrl($url)
    {
        $urls = is_array($url) ? $url : func_get_args();

        return $this->request($urls, 'urls', 'CdnOverseaPushser');
    }

    /**
     * @param $url
     *
     * @return array
     */
    public function pushUrlV2($url)
    {
        $urls = is_array($url) ? $url : func_get_args();

        return $this->request($urls, 'urls', 'CdnPusherV2');
    }

    /**
     * @param $url
     *
     * @return array
     */
    public function refreshUrl($url)
    {
        $urls = is_array($url) ? $url : func_get_args();

        return $this->request($urls, 'urls', 'RefreshCdnUrl');
    }

    /**
     * @param $url
     *
     * @return array
     */
    public function refreshOverseaUrl($url)
    {
        $urls = is_array($url) ? $url : func_get_args();

        return $this->request($urls, 'urls', 'RefreshCdnOverSeaUrl');
    }

    /**
     * @param $dir
     *
     * @return array
     */
    public function refreshDir($dir)
    {
        $dirs = is_array($dir) ? $dir : func_get_args();

        return $this->request($dirs, 'dirs', 'RefreshCdnDir');
    }

    /**
     * @param $dir
     *
     * @return array
     */
    public function refreshOverseaDir($dir)
    {
        $dirs = is_array($dir) ? $dir : func_get_args();

        return $this->request($dirs, 'dirs', 'RefreshCdnOverSeaDir');
    }

    /**
     * @param array  $args
     * @param string $key
     * @param string $action
     *
     * @return array
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
            return sprintf("{$key}.%d", $n);
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
        return $this->getConfig()->get('credentials');
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
     * @throws \InvalidArgumentException if the JSON cannot be decoded.
     *
     * @return array
     */
    protected function normalize($contents)
    {
        return \GuzzleHttp\json_decode($contents, true);
    }

    /**
     * @return \League\Flysystem\Config
     */
    protected function getConfig()
    {
        return $this->filesystem->getConfig();
    }
}
