<?php
/**
 * tests/bootstrap.php
 * 测试引导（纯 PHP，无 Composer/PHPUnit 依赖）：
 *  - 定义 ROOT_DIR、注册自动加载（与 public/index.php 一致）
 *  - resetTestDb()：删表后复用工程师 db/seed.php 灌演示数据
 *  - startServer()/stopServer()：启动 PHP 内置服务器（完整跑 路由 + 中间件 + 控制器）
 *  - req()：以真实 HTTP 请求调用 API，返回 [status, body, raw]
 *  - loginAs()：真实登录换取 token
 *  - registerTest()/runAllTests()：测试注册与执行
 *
 * 设计说明：后端控制器在响应时调用 exit()，且鉴权/数据权限依赖中间件注入 Context，
 * 因此采用「内置 HTTP 服务器 + 真实请求」做集成测试，能覆盖路由、中间件、控制器全链路，
 * 尤其能真实验证 token 失效、scope 隔离、404 路由等行为。
 */

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

// 与 public/index.php 一致的自动加载
spl_autoload_register(function ($class) {
    $map = [
        'Utils\\'      => ROOT_DIR . '/utils/',
        'Router\\'     => ROOT_DIR . '/router/',
        'Middleware\\' => ROOT_DIR . '/middleware/',
        'Controllers\\'=> ROOT_DIR . '/controllers/',
        'Services\\'   => ROOT_DIR . '/services/',
    ];
    foreach ($map as $prefix => $base) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $file = $base . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require_once ROOT_DIR . '/config/config.php';
require_once ROOT_DIR . '/config/db.php';

use Utils\DB;

// 选一个空闲端口，避免与本地其它服务冲突
if (!defined('SERVER_ADDR')) {
    $port = 8901;
    $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($sock) {
        $name = stream_socket_get_name($sock, false);
        fclose($sock);
        if (preg_match('/:(\d+)$/', $name, $m)) {
            $port = (int) $m[1];
        }
    }
    define('SERVER_ADDR', '127.0.0.1:' . $port);
}

$__serverProc = null;

/**
 * 重置测试库：删除全部表后复用工程师 db/seed.php 重新灌演示数据。
 */
function resetTestDb(): void
{
    $pdo = getPDO();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = ['sessions', 'sms_codes', 'accounts', 'sales', 'leaders', 'institutions', 'settings', 'customers'];
    foreach ($tables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    @exec('php ' . escapeshellarg(ROOT_DIR . '/db/seed.php') . ' 2>/dev/null');
}

/**
 * 启动 PHP 内置服务器（后台进程），返回进程资源或 null。
 */
function startServer()
{
    global $__serverProc;

    $docroot = realpath(ROOT_DIR . '/public');
    $router = $docroot . '/index.php';
    $cmd = 'php -S ' . SERVER_ADDR . ' -t ' . escapeshellarg($docroot) . ' ' . escapeshellarg($router);
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd .= ' > NUL 2>&1';
    } else {
        $cmd .= ' > /dev/null 2>&1';
    }

    $proc = @proc_open($cmd, [], $pipes);
    if (!is_resource($proc)) {
        fwrite(STDERR, "无法启动 PHP 内置服务器，请确认 php 在 PATH 中。\n");
        return null;
    }

    // 轮询就绪（服务器对 /api/overview 至少会返回 401 JSON）
    $ready = false;
    for ($i = 0; $i < 60; $i++) {
        $ctx = stream_context_create(['http' => ['timeout' => 0.5, 'ignore_errors' => true]]);
        $r = @file_get_contents('http://' . SERVER_ADDR . '/api/overview', false, $ctx);
        if ($r !== false) {
            $ready = true;
            break;
        }
        usleep(200000);
    }

    $__serverProc = $proc;
    return $ready ? $proc : null;
}

/**
 * 停止内置服务器。
 */
function stopServer(): void
{
    global $__serverProc;
    if (is_resource($__serverProc)) {
        @proc_terminate($__serverProc);
        @proc_close($__serverProc);
    }
    $__serverProc = null;
}

/**
 * 发起一次真实 HTTP 请求。
 * @return array{status:int, body:mixed, raw:string}
 */
function req(string $method, string $path, array $opts = []): array
{
    $url = 'http://' . SERVER_ADDR . $path;
    $query = $opts['query'] ?? [];
    if (!empty($query)) {
        $url .= (strpos($path, '?') === false ? '?' : '&') . http_build_query($query);
    }

    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if (!empty($opts['token'])) {
        $headers[] = 'Authorization: Bearer ' . $opts['token'];
    }

    $ctx = stream_context_create([
        'http' => [
            'method'           => strtoupper($method),
            'header'           => implode("\r\n", $headers),
            'content'          => isset($opts['body']) ? json_encode($opts['body'], JSON_UNESCAPED_UNICODE) : '',
            'ignore_errors'    => true,
            'timeout'          => 15,
            'follow_location'  => 0,
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header) && preg_match('#^HTTP/\d\.\d\s+(\d+)#', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    $data = ($raw !== false) ? @json_decode($raw, true) : null;
    return ['status' => $status, 'body' => $data, 'raw' => (string) $raw];
}

/**
 * 真实登录换取 token（走 /api/auth/login）。
 */
function loginAs(string $phone, string $password = 'admin123'): ?string
{
    $resp = req('POST', '/api/auth/login', ['body' => ['phone' => $phone, 'password' => $password]]);
    return $resp['body']['data']['token'] ?? null;
}

/**
 * 生成唯一手机号（避免演示数据/测试数据唯一键冲突）。
 */
function uniqPhone(): string
{
    return '1' . substr(str_replace('.', '', uniqid('', true)), 0, 10);
}

// ----------------- 测试注册表 -----------------
$GLOBALS['__tests'] = [];

function registerTest(string $name, callable $fn): void
{
    $GLOBALS['__tests'][$name] = $fn;
}

function runAllTests(): array
{
    foreach ($GLOBALS['__tests'] as $name => $fn) {
        echo "\n# " . $name . "\n";
        try {
            $fn();
        } catch (\Throwable $e) {
            \Tests\TestCase::$fail++;
            \Tests\TestCase::$failures[] = $name . ' 抛出异常: ' . $e->getMessage();
            echo '  EXCEPTION: ' . $e->getMessage() . "\n";
        }
    }
    return [\Tests\TestCase::$pass, \Tests\TestCase::$fail];
}
