<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class PutRemoteFileAs extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'putRemoteFileAs';
    }

    /**
     * @param       $path
     * @param       $remoteUrl
     * @param       $name
     * @param array $options
     *
     * @return string|false
     */
    public function handle($path, $remoteUrl, $name, array $options = [])
    {
        //Get file from remote url
        $contents = $this->filesystem
            ->getAdapter()
            ->getHttpClient()
            ->get($remoteUrl)
            ->getBody()
            ->getContents();

        $path = trim($path.'/'.$name, '/');

        return $this->filesystem->put($path, $contents, $options) ? $path : false;
    }
}
