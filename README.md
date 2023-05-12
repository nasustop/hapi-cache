# HapiCache
hyperf的cache扩展组件

## 安装
```
composer require nasustop/hapi-cache
```
### 基于hyperf2.2版本
```
composer require nasustop/hapi-cache:~2.2.0
```
# 使用说明
对`Hyperf/Cache`做了功能扩展
1、增加`MemcachedDriver`
2、增加指定`redis`和`memcached`的`pool`

# 配置文件

## 愿配置文件
```php
<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => Hyperf\Cache\Driver\RedisDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
    ],
];
```

## 增加扩展后的配置文件
```php
<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => \Nasustop\HapiCache\RedisDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
        'pool' => 'default',
    ],
    'memcached' => [
        'driver' => \Nasustop\HapiCache\MemcachedDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
        'pool' => 'default',
    ],
];
```