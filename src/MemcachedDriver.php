<?php

declare(strict_types=1);
/**
 * This file is part of Hapi.
 *
 * @link     https://www.nasus.top
 * @document https://wiki.nasus.top
 * @contact  xupengfei@xupengfei.net
 * @license  https://github.com/nasustop/hapi-cache/blob/master/LICENSE
 */
namespace Nasustop\HapiCache;

use DateInterval;
use Hyperf\Cache\Driver\Driver;
use Hyperf\Cache\Driver\KeyCollectorInterface;
use Hyperf\Cache\Exception\InvalidArgumentException;
use Hyperf\Contract\ConfigInterface;
use Nasustop\HapiMemcached\Memcached;
use Nasustop\HapiMemcached\MemcachedFactory;
use Psr\Container\ContainerInterface;

class MemcachedDriver extends Driver implements KeyCollectorInterface
{
    protected Memcached $memcached;

    public function __construct(ContainerInterface $container, array $config)
    {
        parent::__construct($container, $config);

        $this->memcached = $container->get(MemcachedFactory::class)->get($config['pool'] ?? 'default');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $res = $this->memcached->get($this->getCacheKey($key));
        if ($res === false) {
            return $default;
        }

        return $this->packer->unpack($res);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $seconds = $this->secondsUntil($ttl);
        $res = $this->packer->pack($value);
        if ($seconds > 0) {
            return $this->memcached->set($this->getCacheKey($key), $res, $seconds);
        }

        return $this->memcached->set($this->getCacheKey($key), $res);
    }

    public function delete(string $key): bool
    {
        return (bool) $this->memcached->delete($this->getCacheKey($key));
    }

    public function clear(): bool
    {
        return $this->memcached->flush();
    }

    public function getMultiple($keys, mixed $default = null): iterable
    {
        $cacheKeys = array_map(function ($key) {
            return $this->getCacheKey($key);
        }, $keys);

        $values = $this->memcached->getMulti($cacheKeys);
        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] === false ? $default : $this->packer->unpack($values[$i]);
        }

        return $result;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        if (! is_array($values)) {
            throw new InvalidArgumentException('The values is invalid!');
        }

        $cacheKeys = [];
        foreach ($values as $key => $value) {
            $cacheKeys[$this->getCacheKey($key)] = $this->packer->pack($value);
        }

        $seconds = $this->secondsUntil($ttl);
        if ($seconds > 0) {
            foreach ($cacheKeys as $key => $value) {
                $this->memcached->set($key, $value, $seconds);
            }

            return true;
        }

        return $this->memcached->setMulti($cacheKeys);
    }

    public function deleteMultiple($keys): bool
    {
        $cacheKeys = array_map(function ($key) {
            return $this->getCacheKey($key);
        }, $keys);

        return (bool) $this->memcached->deleteMulti($cacheKeys);
    }

    public function has(string $key): bool
    {
        $res = $this->memcached->get($this->getCacheKey($key));
        return ! ($res === false);
    }

    public function fetch(string $key, $default = null): array
    {
        $res = $this->memcached->get($this->getCacheKey($key));
        if ($res === false) {
            return [false, $default];
        }

        return [true, $this->packer->unpack($res)];
    }

    public function clearPrefix(string $prefix): bool
    {
        throw new InvalidArgumentException('Memcached不支持清除指定prefix的数据');
    }

    public function addKey(string $collector, string $key): bool
    {
        $cacheData = $this->get($collector, []);
        $cacheData[$key] = time();
        return $this->set($collector, $cacheData);
    }

    public function keys(string $collector): array
    {
        $cacheData = $this->get($collector, []);
        return array_keys($cacheData);
    }

    public function delKey(string $collector, string ...$key): bool
    {
        $cacheData = $this->get($collector, []);
        foreach ($key as $k) {
            unset($cacheData[$k]);
        }
        $cacheData = array_values(array_filter($cacheData));
        return $this->set($collector, $cacheData);
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->container->get(ConfigInterface::class)->get($key, $default);
    }
}
