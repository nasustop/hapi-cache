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

namespace HyperfTest\Cases;

use Hyperf\Cache\Driver\RedisDriver;
use Hyperf\Contract\ConfigInterface;
use Nasustop\HapiCache\Listener\BeforeMainServerStartListener;
use Nasustop\HapiCache\Listener\BeforeWorkerStartListener;
use Nasustop\HapiCache\MemoryDriver;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * 监听器解析 cache 配置的健壮性。
 *
 * 背景：Hyperf 3.x 支持「default 为 store 名 + stores 为定义」的两段式配置，
 * 例如：
 *   return ['default' => 'default', 'stores' => [...]];
 * 此时 `cache` 下存在字符串值。3.0.2/3.0.3 的监听器直接 $value['driver']，
 * 在字符串上取下标会抛：
 *   TypeError: Cannot access offset of type string on string
 * 且该异常发生在 BeforeMainServerStart 阶段，直接导致服务启动失败。
 *
 * @internal
 * @coversNothing
 */
class ListenerCacheConfigTest extends TestCase
{
    /**
     * 新式两段式配置（default 为字符串）不应抛异常。
     */
    public function testNewStyleConfigWithStringDefaultDoesNotThrow()
    {
        $config = $this->createConfig([
            'default' => 'default',
            'stores' => [
                'default' => [
                    'driver' => RedisDriver::class,
                ],
                'memory' => [
                    'driver' => MemoryDriver::class,
                    'size' => 1024,
                ],
            ],
        ]);

        $listener = new BeforeMainServerStartListener($config);
        $this->assertInstanceOf(BeforeMainServerStartListener::class, $listener);

        // 通过反射调用私有解析方法，确保不抛 TypeError 且能取到 memory store
        $method = new ReflectionMethod($listener, 'resolveMemoryStoreConfig');
        $method->setAccessible(true);
        $result = $method->invoke($listener);

        $this->assertIsArray($result);
        $this->assertSame(MemoryDriver::class, $result['driver'] ?? null);
    }

    /**
     * 旧式配置（cache 直接是 store 定义集合）仍应可用。
     */
    public function testLegacyConfigIsStillSupported()
    {
        $config = $this->createConfig([
            'default' => [
                'driver' => RedisDriver::class,
            ],
            'memory' => [
                'driver' => MemoryDriver::class,
                'size' => 512,
            ],
        ]);

        $listener = new BeforeWorkerStartListener($config);
        $method = new ReflectionMethod($listener, 'resolveMemoryStoreConfig');
        $method->setAccessible(true);
        $result = $method->invoke($listener);

        $this->assertSame(MemoryDriver::class, $result['driver'] ?? null);
        $this->assertSame(512, $result['size'] ?? null);
    }

    /**
     * 没有任何 memory store 时返回空数组，调用方应安全 return。
     */
    public function testNoMemoryStoreReturnsEmptyArray()
    {
        $config = $this->createConfig([
            'default' => 'default',
            'stores' => [
                'default' => [
                    'driver' => RedisDriver::class,
                ],
            ],
        ]);

        $listener = new BeforeMainServerStartListener($config);
        $method = new ReflectionMethod($listener, 'resolveMemoryStoreConfig');
        $method->setAccessible(true);

        $this->assertSame([], $method->invoke($listener));
    }

    private function createConfig(array $cacheConfig): ConfigInterface
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnCallback(
            static fn (string $key, $default = null) => $key === 'cache' ? $cacheConfig : $default
        );

        return $config;
    }
}
