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
use Hyperf\Cache\CacheManager;
use Hyperf\Context\ApplicationContext;
use Psr\SimpleCache\CacheInterface;

if (! function_exists('cache')) {
    /**
     * 获取 cache.
     *
     * Hyperf 3.x 起 ApplicationContext 位于 Hyperf\Context 命名空间，
     * 原 Hyperf\Utils\ApplicationContext 已被删除。
     *
     * 注：原先的 try/catch + make() 兜底已移除——make() 内部同样调用
     * ApplicationContext::getContainer()，容器缺失时无法兜底，
     * 只是把异常换成另一种形式而已。
     */
    function cache(string $driver = 'default'): CacheInterface
    {
        return ApplicationContext::getContainer()->get(CacheManager::class)->getDriver($driver);
    }
}
