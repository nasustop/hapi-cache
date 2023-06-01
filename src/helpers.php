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
if (! function_exists('cache')) {
    /**
     * 获取cache.
     */
    function cache(string $driver = 'default'): Psr\SimpleCache\CacheInterface
    {
        try {
            return \Hyperf\Utils\ApplicationContext::getContainer()->get(Hyperf\Cache\CacheManager::class)->getDriver($driver);
        } catch (Psr\Container\NotFoundExceptionInterface|Psr\Container\ContainerExceptionInterface $e) {
            return make(\Hyperf\Cache\CacheManager::class)->getDriver($driver);
        }
    }
}
