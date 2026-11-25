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

/**
 * 防止 Hyperf 2.x 命名空间残留（Hyperf\Utils\* 在 3.x 已删除）。
 *
 * 背景：3.0.1 / 3.0.2 的 src/helpers.php 仍使用
 * `\Hyperf\Utils\ApplicationContext`，在 Hyperf 3.x 下调用 cache()
 * 会抛 `Class "Hyperf\Utils\ApplicationContext" not found`，
 * 且由于 helpers.php 走 composer files 自动加载，会覆盖宿主项目的
 * cache() 实现，导致所有用到 cache() 的接口 500。
 *
 * @internal
 * @coversNothing
 */
class Hyperf3CompatibilityTest extends AbstractTestCase
{
    /**
     * src 下不允许出现任何 Hyperf\Utils\* 引用。
     */
    public function testNoDeprecatedHyperfUtilsNamespace()
    {
        $offenders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../src', \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (strpos($content, 'Hyperf\\Utils\\') !== false) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            '以下文件仍引用 Hyperf\Utils\*（3.x 已删除）: ' . implode(', ', $offenders)
        );
    }

    /**
     * cache() 必须能被调用，且返回 Psr\SimpleCache\CacheInterface。
     * 该断言在 Hyperf 3.x 下能直接暴露 ApplicationContext 命名空间错误。
     */
    public function testCacheHelperIsCallable()
    {
        $this->assertTrue(function_exists('cache'));
        $this->assertInstanceOf(
            \Psr\SimpleCache\CacheInterface::class,
            cache('default')
        );
    }
}
