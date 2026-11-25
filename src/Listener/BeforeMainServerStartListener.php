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

namespace Nasustop\HapiCache\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Nasustop\HapiCache\MemoryDriver;
use Swoole\Table;

class BeforeMainServerStartListener implements ListenerInterface
{
    public function __construct(private ConfigInterface $config) {}

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        $config = $this->resolveMemoryStoreConfig();
        if (empty($config)) {
            return;
        }
        if ($event instanceof BeforeMainServerStart) {
            $table = new Table($config['size'] ?? 1024);
            $table->column('data', Table::TYPE_STRING, $config['row_size'] ?? 1024);
            $table->column('ttl', Table::TYPE_INT);
            $table->create();
            $event->server->cache_table = $table;
        }
    }

    /**
     * 找出 cache 配置中使用 MemoryDriver 的 store。
     *
     * Hyperf 3.x 支持「default 为 store 名 + stores 为定义」的两段式配置，
     * 此时 cache 下会存在字符串值（如 'default' => 'default'），
     * 不能直接当作数组访问 $value['driver']（否则 TypeError）。
     */
    private function resolveMemoryStoreConfig(): array
    {
        $cache = (array) $this->config->get('cache', []);

        // 新式配置：store 定义在 cache.stores 下
        $stores = $cache['stores'] ?? null;
        if (! is_array($stores)) {
            // 旧式配置：cache 直接是 store 定义集合
            $stores = $cache;
        }

        foreach ($stores as $value) {
            if (is_array($value) && ($value['driver'] ?? null) === MemoryDriver::class) {
                return $value;
            }
        }

        return [];
    }
}
