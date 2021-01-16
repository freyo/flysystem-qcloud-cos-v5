<div>
  <p align="center">
    <image src="https://imgcache.qq.com/open_proj/proj_qcloud_v2/international/doc/css/img/icon/icon-storage.svg" width="150" height="150">
  </p>
  <p align="center">Flysystem Adapter for <a href="https://github.com/tencentyun/cos-php-sdk-v5">Tencent Cloud Object Storage</a></p>
  <p align="center">
    <a href="LICENSE">
      <image src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License">
    </a>
    <a href="https://travis-ci.org/freyo/flysystem-qcloud-cos-v5">
      <image src="https://img.shields.io/travis/freyo/flysystem-qcloud-cos-v5/master.svg?style=flat-square" alt="Build Status">
    </a>
    <a href="https://scrutinizer-ci.com/g/freyo/flysystem-qcloud-cos-v5">
      <image src="https://img.shields.io/scrutinizer/coverage/g/freyo/flysystem-qcloud-cos-v5.svg?style=flat-square" alt="Coverage Status">
    </a>
    <a href="https://scrutinizer-ci.com/g/freyo/flysystem-qcloud-cos-v5">
      <image src="https://img.shields.io/scrutinizer/g/freyo/flysystem-qcloud-cos-v5.svg?style=flat-square" alt="Quality Score">
    </a>
    <a href="https://packagist.org/packages/freyo/flysystem-qcloud-cos-v5">
      <image src="https://img.shields.io/packagist/v/freyo/flysystem-qcloud-cos-v5.svg?style=flat-square" alt="Packagist Version">
    </a>
    <a href="https://packagist.org/packages/freyo/flysystem-qcloud-cos-v5">
      <image src="https://img.shields.io/packagist/dt/freyo/flysystem-qcloud-cos-v5.svg?style=flat-square" alt="Total Downloads">
    </a>
  </p>
  <p align="center">
    <a href="https://app.fossa.io/projects/git%2Bgithub.com%2Ffreyo%2Fflysystem-qcloud-cos-v5?ref=badge_small">
      <img src="https://app.fossa.io/api/projects/git%2Bgithub.com%2Ffreyo%2Fflysystem-qcloud-cos-v5.svg?type=small"  alt="FOSSA Status">
    </a>
  </p>
</div>

## Installation

  > Support Laravel/Lumen 5.x/6.x/7.x/8.x

  ```shell
  composer require "freyo/flysystem-qcloud-cos-v5:^2.0" -vvv
  ```

## Bootstrap

  ```php
  <?php
  use Freyo\Flysystem\QcloudCOSv5\Adapter;
  use League\Flysystem\Filesystem;
  use Qcloud\Cos\Client;

  include __DIR__ . '/vendor/autoload.php';

  $config = [
      'region'          => 'ap-guangzhou',
      'credentials'     => [
          'appId'     => 'your-app-id',
          'secretId'  => 'your-secret-id',
          'secretKey' => 'your-secret-key',
          'token'     => null,
      ],
      'timeout'         => 60,
      'connect_timeout' => 60,
      'bucket'          => 'your-bucket-name',
      'cdn'             => '',
      'scheme'          => 'https',
      'read_from_cdn'   => false,
      'cdn_key'         => '',
      'encrypt'         => false,
  ];
  
  $client     = new Client($config);
  $adapter    = new Adapter($client, $config);
  $filesystem = new Filesystem($adapter);
  ```

### API

```php
bool $flysystem->write('file.md', 'contents');

bool $flysystem->writeStream('file.md', fopen('path/to/your/local/file.jpg', 'r'));

bool $flysystem->update('file.md', 'new contents');

bool $flysystem->updateStram('file.md', fopen('path/to/your/local/file.jpg', 'r'));

bool $flysystem->rename('foo.md', 'bar.md');

bool $flysystem->copy('foo.md', 'foo2.md');

bool $flysystem->delete('file.md');

bool $flysystem->has('file.md');

string|false $flysystem->read('file.md');

array $flysystem->listContents();

array $flysystem->getMetadata('file.md');

int $flysystem->getSize('file.md');

string $flysystem->getUrl('file.md'); 

string $flysystem->getTemporaryUrl('file.md', date_create('2018-12-31 18:12:31')); 

string $flysystem->getMimetype('file.md');

int $flysystem->getTimestamp('file.md');

string $flysystem->getVisibility('file.md');

bool $flysystem->setVisibility('file.md', 'public'); //or 'private', 'default'
```

[Full API documentation.](http://flysystem.thephpleague.com/api/)

## Use in Laravel
  
**Laravel 5.5+ uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.**

1. Register the service provider in `config/app.php`:

  ```php
  'providers' => [
    // ...
    Freyo\Flysystem\QcloudCOSv5\ServiceProvider::class,
  ]
  ```

2. Configure `config/filesystems.php`:

  ```php
  'disks'=>[
      // ...
      'cosv5' => [
            'driver' => 'cosv5',
            'region'          => env('COSV5_REGION', 'ap-guangzhou'),
            'credentials'     => [
                'appId'     => env('COSV5_APP_ID'),
                'secretId'  => env('COSV5_SECRET_ID'),
                'secretKey' => env('COSV5_SECRET_KEY'),
                'token'     => env('COSV5_TOKEN'),
            ],
            'timeout'         => env('COSV5_TIMEOUT', 60),
            'connect_timeout' => env('COSV5_CONNECT_TIMEOUT', 60),
            'bucket'          => env('COSV5_BUCKET'),
            'cdn'             => env('COSV5_CDN'),
            'scheme'          => env('COSV5_SCHEME', 'https'),
            'read_from_cdn'   => env('COSV5_READ_FROM_CDN', false),
            'cdn_key'         => env('COSV5_CDN_KEY'),
            'encrypt'         => env('COSV5_ENCRYPT', false),
      ],
  ],
  ```

3. Configure `.env`:
  
  ```php
  COSV5_APP_ID=
  COSV5_SECRET_ID=
  COSV5_SECRET_KEY=
  COSV5_TOKEN=null
  COSV5_TIMEOUT=60
  COSV5_CONNECT_TIMEOUT=60
  COSV5_BUCKET=
  COSV5_REGION=ap-guangzhou
  COSV5_CDN=
  COSV5_SCHEME=https
  COSV5_READ_FROM_CDN=false
  COSV5_CDN_KEY=
  COSV5_ENCRYPT=false
  ```

## Use in Lumen

1. Add the following code to your `bootstrap/app.php`:

  ```php
  $app->singleton('filesystem', function ($app) {
      $app->alias('filesystem', Illuminate\Contracts\Filesystem\Factory::class);
      return $app->loadComponent(
          'filesystems',
          Illuminate\Filesystem\FilesystemServiceProvider::class,
          'filesystem'
      );
  });
  ```

2. And this:
  
  ```php
  $app->register(Freyo\Flysystem\QcloudCOSv5\ServiceProvider::class);
  ```

3. Configure `.env`:
  
  ```php
  COSV5_APP_ID=
  COSV5_SECRET_ID=
  COSV5_SECRET_KEY=
  COSV5_TOKEN=null
  COSV5_TIMEOUT=60
  COSV5_CONNECT_TIMEOUT=60
  COSV5_BUCKET=
  COSV5_REGION=ap-guangzhou
  COSV5_CDN=
  COSV5_SCHEME=https
  COSV5_READ_FROM_CDN=false
  COSV5_CDN_KEY=
  COSV5_ENCRYPT=false
  ```

### Usage

```php
$disk = Storage::disk('cosv5');

// create a file
$disk->put('avatars/1', $fileContents);

// check if a file exists
$exists = $disk->has('file.jpg');

// get timestamp
$time = $disk->lastModified('file1.jpg');

// copy a file
$disk->copy('old/file1.jpg', 'new/file1.jpg');

// move a file
$disk->move('old/file1.jpg', 'new/file1.jpg');

// get file contents
$contents = $disk->read('folder/my_file.txt');

// get url
$url = $disk->url('new/file1.jpg');
$temporaryUrl = $disk->temporaryUrl('new/file1.jpg', Carbon::now()->addMinutes(5));

// create a file from remote(plugin)
$disk->putRemoteFile('avatars/1', 'http://example.org/avatar.jpg');
$disk->putRemoteFileAs('avatars/1', 'http://example.org/avatar.jpg', 'file1.jpg');

// refresh cdn cache(plugin)
$disk->cdn()->refreshUrl(['http://your-cdn-host/path/to/avatar.jpg']);
$disk->cdn()->refreshDir(['http://your-cdn-host/path/to/']);
$disk->cdn()->pushUrl(['http://your-cdn-host/path/to/avatar.jpg']);
$disk->cdn()->refreshOverseaUrl(['http://your-cdn-host/path/to/avatar.jpg']);
$disk->cdn()->refreshOverseaDir(['http://your-cdn-host/path/to/']);
$disk->cdn()->pushOverseaUrl(['http://your-cdn-host/path/to/avatar.jpg']);

// cdn url signature(plugin)
$url = 'http://www.test.com/1.mp4';
$disk->cdn()->signatureA($url, $key = null, $timestamp = null, $random = null, $signName = 'sign');
$disk->cdn()->signatureB($url, $key = null, $timestamp = null);
$disk->cdn()->signatureC($url, $key = null, $timestamp = null);
$disk->cdn()->signatureD($url, $key = null, $timestamp = null, $signName = 'sign', $timeName = 't');

// tencent captcha(plugin)
$disk->tcaptcha($aid, $appSecretKey)->verify($ticket, $randStr, $userIP);

// get federation token(plugin)
$disk->getFederationToken($path = '*', $seconds = 7200, Closure $customPolicy = null, $name = 'cos')
$disk->getFederationTokenV3($path = '*', $seconds = 7200, Closure $customPolicy = null, $name = 'cos')

// tencent image process(plugin)
$disk->cloudInfinite()->imageProcess($objectKey, array $picOperations);
$disk->cloudInfinite()->contentRecognition($objectKey, array $contentRecognition);
```

[Full API documentation.](https://laravel.com/api/8.x/Illuminate/Contracts/Filesystem/Cloud.html)

## Regions & Endpoints

[Official Documentation](https://intl.cloud.tencent.com/document/product/436/6224?lang=en)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Ffreyo%2Fflysystem-qcloud-cos-v5.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Ffreyo%2Fflysystem-qcloud-cos-v5?ref=badge_large)
