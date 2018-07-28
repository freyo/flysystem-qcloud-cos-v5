<div>
  <p align="center">
    <image src="https://mc.qcloudimg.com/static/img/e9ea555bef030eb7b380e9a3a1e59323/COS.svg" width="220" height="220">
  </p>
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
  <p align="center">This is a Flysystem adapter for the qcloud-cos-sdk-php v5.</p>
  <p align="center">腾讯云COS对象存储 V5</p>
  <p align="center">安全稳定、海量、便捷、低延迟、低成本的云端存储服务</p>
</div>

## Attention

JSON API 接口与标准 XML 的 API 底层架构相同，数据互通，可以交叉使用，但是接口不兼容，域名不一致。

腾讯云 COS 的 XML API 服务推出后，推荐您使用 XML API 接口， JSON API 接口日后将保持维护状态，可以正常使用但是不发展新特性。

[XML API 与 JSON API 常见问题](https://cloud.tencent.com/document/product/436/6281)

COS 的可用地域（Region）请参见 [#Region](#region)

## Installation

  ```shell
  composer require freyo/flysystem-qcloud-cos-v5
  ```

## Bootstrap

  ```php
  <?php
  use Freyo\Flysystem\QcloudCOSv5\Adapter;
  use League\Flysystem\Filesystem;

  include __DIR__ . '/vendor/autoload.php';

  $config = [
      'region'          => 'ap-guangzhou',
      'credentials'     => [
          'appId'     => 'your-app-id',
          'secretId'  => 'your-secret-id',
          'secretKey' => 'your-secret-key',
      ],
      'timeout'         => 60,
      'connect_timeout' => 60,
      'bucket'          => 'your-bucket-name',
      'cdn'             => '', // default: https://{your-bucket-name}-{your-app-id}.file.myqcloud.com
      'scheme'          => 'https',
  ];

  $adapter = new Adapter($config);
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

bool $flysystem->setVisibility('file.md', 'public'); //or 'private'
```

[Full API documentation.](http://flysystem.thephpleague.com/api/)

## Use in Laravel
  
**Laravel 5.5 uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.**

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
            ],
            'timeout'         => env('COSV5_TIMEOUT', 60),
            'connect_timeout' => env('COSV5_CONNECT_TIMEOUT', 60),
            'bucket'          => env('COSV5_BUCKET'),
            'cdn'             => env('COSV5_CDN'),
            'scheme'          => env('COSV5_SCHEME', 'https'),
      ],
  ],
  ```

3. Configure `.env`:
  
  ```php
  COSV5_APP_ID=
  COSV5_SECRET_ID=
  COSV5_SECRET_KEY=
  COSV5_TIMEOUT=60
  COSV5_CONNECT_TIMEOUT=60
  COSV5_BUCKET=
  COSV5_REGION=ap-guangzhou
  COSV5_CDN= #https://{your-bucket-name}-{your-app-id}.file.myqcloud.com
  COSV5_SCHEME=https
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
  COSV5_TIMEOUT=60
  COSV5_CONNECT_TIMEOUT=60
  COSV5_BUCKET=
  COSV5_REGION=ap-guangzhou
  COSV5_CDN= #https://{your-bucket-name}-{your-app-id}.file.myqcloud.com
  COSV5_SCHEME=https
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

// create a file from remote(plugin support)
$disk->putRemoteFile('avatars/1', 'http://example.org/avatar.jpg');
$disk->putRemoteFileAs('avatars/1', 'http://example.org/avatar.jpg', 'file1.jpg');

// refresh cdn cache(plugin support)
$disk->cdn()->refreshUrl(['http://your-cdn-host/path/to/avatar.jpg']);
$disk->cdn()->refreshDir(['http://your-cdn-host/path/to/']);
```

[Full API documentation.](https://laravel.com/api/5.5/Illuminate/Contracts/Filesystem/Cloud.html)

## Region

|地区|区域表示|AP|
|:-:|:-:|:-:|
|上海（华东）|cn-east / sh|ap-shanghai|
|广州（华南）|cn-sorth / gz|ap-guangzhou|
|天津（华北）|cn-north / tj|ap-beijing-1|
|成都（西南）|cn-southwest / cd|ap-chengdu|
|新加坡|sg / sgp|ap-singapore|
|北京|bj|ap-beijing|

Recommend using `ap-*`

[Official Documentation](https://cloud.tencent.com/document/product/436/6224)
