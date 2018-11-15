<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;
use League\Flysystem\Util\MimeType;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

class PutRemoteFile extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'putRemoteFile';
    }

    /**
     * @param       $path
     * @param       $remoteUrl
     * @param array $options
     *
     * @return string|false
     */
    public function handle($path, $remoteUrl, array $options = [])
    {
        //Get file from remote url
        $contents = $this->filesystem
            ->getAdapter()
            ->getHttpClient()
            ->get($remoteUrl)
            ->getBody()
            ->getContents();

        $filename = md5($contents);
        $extension = ExtensionGuesser::getInstance()->guess(MimeType::detectByContent($contents));
        $name = $filename.'.'.$extension;

        $path = trim($path.'/'.$name, '/');

        return $this->filesystem->put($path, $contents, $options) ? $path : false;
    }
}
