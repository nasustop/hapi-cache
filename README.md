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
- 增加`MemcachedDriver`
- 增加指定`redis`和`memcached`的`pool`
- 增加`memory`缓存类型，基于`Swoole\Table`实现

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
    'memory' => [
        'driver' => \Nasustop\HapiCache\MemoryDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'size' => 10240, // 最大缓存行数
        'memory_size' => 1024 * 1024 * 1024 * 2, // 最大占用内存
        'row_size' => 4096, // 每个缓存的最大长度
        'ttl' => 3600 * 24 * 365, // 默认缓存时间
        'clean_size' => 500, // 超出最大缓存行数或最大占用内存时，删除旧数据的数量，小于等于0时全部删除
    ],
];
```