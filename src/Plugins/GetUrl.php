<?php

namespace Freyo\Flysystem\QcloudCOSv5\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class GetUrl extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getUrl';
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public function handle($path)
    {
        return $this->filesystem->getAdapter()->applyPathPrefix($path);
    }
}
