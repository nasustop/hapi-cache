<?php

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