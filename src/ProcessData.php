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

use Swoole\Table;

class ProcessData
{
    public static ?int $work_id;

    public static ?Table $table;
}
