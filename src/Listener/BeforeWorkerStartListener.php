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
use Hyperf\Framework\Event\BeforeWorkerStart;
use Nasustop\HapiCache\MemoryDriver;
use Nasustop\HapiCache\ProcessData;
use Swoole\Table;

class BeforeWorkerStartListener implements ListenerInterface
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function listen(): array
    {
        return [
            BeforeWorkerStart::class,
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
        if ($event instanceof BeforeWorkerStart) {
            ProcessData::$work_id = $event->workerId;
            $table = $event->server->cache_table ?? null;
            if ($table instanceof Table) {
                ProcessData::$table = $table;
            }
        }
    }
}
