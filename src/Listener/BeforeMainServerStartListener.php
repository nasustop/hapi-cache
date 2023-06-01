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
    public function __construct(private ConfigInterface $config)
    {
    }

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        $cache = $this->config->get('cache', []);
        $config = [];
        foreach ($cache as $value) {
            if ($value['driver'] === MemoryDriver::class) {
                $config = $value;
            }
        }
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
}
