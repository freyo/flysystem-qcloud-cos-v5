<?php

namespace Freyo\Flysystem\QcloudCOSv5;

use Freyo\Flysystem\QcloudCOSv5\Plugins\GetUrl;
use Freyo\Flysystem\QcloudCOSv5\Plugins\PutRemoteFile;
use Freyo\Flysystem\QcloudCOSv5\Plugins\PutRemoteFileAs;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use League\Flysystem\Filesystem;
use Qcloud\Cos\Client;

/**
 * Class ServiceProvider.
 */
class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app instanceof LumenApplication) {
            $this->app->configure('filesystems');
        }

        $this->app->make('filesystem')
                  ->extend('cosv5', function ($app, $config) {
                      $client = new Client($config);
                      $flysystem = new Filesystem(new Adapter($client, $config));

                      $flysystem->addPlugin(new PutRemoteFile());
                      $flysystem->addPlugin(new PutRemoteFileAs());
                      $flysystem->addPlugin(new GetUrl());

                      return $flysystem;
                  });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/filesystems.php', 'filesystems'
        );
    }
}
