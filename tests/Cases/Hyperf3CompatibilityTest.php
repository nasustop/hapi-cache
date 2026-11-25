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

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../src', FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            // 只匹配真实代码（use 语句、静态调用等），排除注释中的说明文字
            $code = '';
            foreach (token_get_all($content) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $code .= is_array($token) ? $token[1] : $token;
            }
            if (strpos($code, 'Hyperf\Utils\\') !== false) {
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
     * cache() 必须已注册（本包通过 autoload.files 提供）。
     *
     * 注意：这里不断言「调用 cache() 能拿到驱动」——该断言需要运行中的
     * Hyperf 容器，在纯 PHPUnit 环境下 ApplicationContext 无容器，
     * 会抛 getContainer(): Return value must be of type ContainerInterface。
     * 命名空间错误由上面的静态扫描负责拦截。
     */
    public function testCacheHelperIsRegistered()
    {
        $this->assertTrue(function_exists('cache'));
    }
}
