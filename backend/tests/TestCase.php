<?php
/**
 * tests/TestCase.php
 * 轻量断言助手（无 PHPUnit 依赖），统一输出 PASS/FAIL。
 */

namespace Tests;

class TestCase
{
    /** @var int 通过数 */
    public static int $pass = 0;

    /** @var int 失败数 */
    public static int $fail = 0;

    /** @var array 失败信息 */
    public static array $failures = [];

    public static function assert(bool $cond, string $msg): void
    {
        if ($cond) {
            self::$pass++;
            echo '  PASS: ' . $msg . "\n";
        } else {
            self::$fail++;
            self::$failures[] = $msg;
            echo '  FAIL: ' . $msg . "\n";
        }
    }

    public static function assertEqual($expected, $actual, string $msg): void
    {
        self::assert($expected === $actual, $msg . ' [expected=' . self::desc($expected) . ', actual=' . self::desc($actual) . ']');
    }

    public static function assertTrue($cond, string $msg): void
    {
        self::assert((bool) $cond, $msg);
    }

    public static function assertStatus(int $expected, int $actual, string $msg): void
    {
        self::assert($expected === $actual, $msg . ' [HTTP status expected=' . $expected . ', actual=' . $actual . ']');
    }

    /**
     * 断言 API 返回成功（HTTP 200 且业务 code=0）。
     */
    public static function assertOk(array $resp, string $msg): void
    {
        $status = $resp['status'] ?? 0;
        $code = $resp['body']['code'] ?? null;
        self::assert($status === 200 && $code === 0, $msg . ' [status=' . $status . ', code=' . $code . ']');
    }

    private static function desc($v): string
    {
        if (is_array($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if ($v === null) {
            return 'null';
        }
        return (string) $v;
    }
}
