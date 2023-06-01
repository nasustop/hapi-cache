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

use Hyperf\Cache\Driver\Driver;
use Hyperf\Cache\Driver\KeyCollectorInterface;
use Hyperf\Cache\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Swoole\Table;

class MemoryDriver extends Driver implements KeyCollectorInterface
{
    protected Table $table;

    protected int $ttl;

    protected int $size;

    protected int $memory_size;

    protected int $row_size;

    protected int $clean_size = 0;

    public function __construct(ContainerInterface $container, array $config)
    {
        parent::__construct($container, $config);

        $this->table = ProcessData::$table;
        $this->ttl = $config['ttl'] ?? 24 * 3600 * 365 * 10;
        $this->size = $this->table->getSize() - 10;
        $this->memory_size = $config['memory_size'] ?? 1024 * 1024 * 2;
        $this->row_size = $config['row_size'] ?? 1024;
        $this->clean_size = $config['clean_size'] ?? 0;
    }

    public function get($key, $default = null)
    {
        $res = $this->table->get($key);
        if ($res === false) {
            return $default;
        }
        if ($res['ttl'] > 0 && time() > $res['ttl']) {
            $this->delete($key);
            return $default;
        }
        return $this->packer->unpack($res['data']);
    }

    public function set($key, $value, $ttl = null)
    {
        $value = $this->packer->pack($value);
        if (mb_strlen($value) > $this->row_size) {
            throw new InvalidArgumentException('缓存内容的最大长度为：' . $this->row_size);
        }
        $this->checkMemorySize();
        $seconds = $this->secondsUntil($ttl);

        return $this->table->set($key, [
            'data' => $value,
            'ttl' => time() + ($seconds > 0 ? $seconds : $this->ttl),
        ]);
    }

    public function delete($key)
    {
        return $this->table->del($key);
    }

    public function clear(): bool
    {
        $keys = [];
        foreach ($this->table as $key => $row) {
            $keys[] = $key;
        }
        foreach ($keys as $key) {
            $this->table->del($key);
        }
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key)
    {
        $res = $this->get($key, false);
        return ! ($res === false);
    }

    public function fetch(string $key, $default = null): array
    {
        $res = $this->get($key, false);
        if ($res === false) {
            return [false, $default];
        }

        return [true, $res];
    }

    public function clearPrefix(string $prefix): bool
    {
        $keys = [];
        foreach ($this->table as $key => $row) {
            $keys[] = $key;
        }
        foreach ($keys as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->table->del($key);
            }
        }
        return true;
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

    public function delKey(string $collector, ...$key): bool
    {
        $cacheData = $this->get($collector, []);
        foreach ($key as $k) {
            unset($cacheData[$k]);
        }
        $cacheData = array_values(array_filter($cacheData));
        return $this->set($collector, $cacheData);
    }

    /**
     * 行数或内存超出限制，清除部分数据.
     */
    protected function checkMemorySize(): bool
    {
        $size = $this->table->count();
        $memory_size = $this->table->getMemorySize();
        if ($size >= $this->size || $memory_size >= $this->memory_size) {
            // 清除全部数据
            if ($this->clean_size <= 0) {
                return $this->clear();
            }
            // 清除部分数据
            $keys = [];
            foreach ($this->table as $key => $row) {
                $keys[] = ['key' => $key, 'ttl' => $row['ttl']];
            }
            $sort = array_column($keys, 'ttl');
            array_multisort($sort, SORT_ASC, $keys);
            $num = 0;
            foreach ($keys as $row) {
                $this->delete($row['key']);
                ++$num;
                if ($num >= $this->clean_size) {
                    break;
                }
            }
        }
        return true;
    }
}
