<?php
/**
 * public/index.php
 * 单一入口。解析 REQUEST_METHOD / REQUEST_URI，路由到 Router。
 * 所有 /api/* 走这里；非 api 返回 404 JSON。
 */

declare(strict_types=1);

// 项目根目录（public 的上一级）
define('ROOT_DIR', dirname(__DIR__));

// 简易 PSR-4 自动加载（无 Composer / 无第三方库）
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
        $relative = substr($class, $len);
        $file = $base . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require_once ROOT_DIR . '/config/config.php';
require_once ROOT_DIR . '/config/db.php';

// 全局 CORS（开发态方便前端联调）
if (defined('CORS') && CORS) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

use Router\Router;
use Utils\Response;
use Middleware\AuthMiddleware;
use Middleware\ScopeMiddleware;

$router = new Router();

$auth  = [AuthMiddleware::class, 'handle'];
$scope = [ScopeMiddleware::class, 'handle'];

// ---------------- Auth ----------------
$router->add('POST', '/api/auth/login',       'AuthController', 'login');
$router->add('POST', '/api/auth/login-sms',   'AuthController', 'loginSms');
$router->add('POST', '/api/auth/send-code',   'AuthController', 'sendCode');
$router->add('POST', '/api/auth/logout',      'AuthController', 'logout', [$auth, $scope]);

// ---------------- Orgs（platform） ----------------
$router->add('GET',    '/api/orgs',              'OrgController', 'index',    [$auth, $scope]);
$router->add('POST',   '/api/orgs',              'OrgController', 'store',    [$auth, $scope]);
$router->add('GET',    '/api/orgs/:id',         'OrgController', 'show',     [$auth, $scope]);
$router->add('PUT',    '/api/orgs/:id',         'OrgController', 'update',   [$auth, $scope]);
$router->add('DELETE', '/api/orgs/:id',         'OrgController', 'destroy',  [$auth, $scope]);
$router->add('PATCH',  '/api/orgs/:id/status',  'OrgController', 'setStatus',[$auth, $scope]);

// ---------------- Upload（需登录） ----------------
$router->add('POST', '/api/upload', 'OrgController', 'upload', [$auth]);

// ---------------- Leaders（platform|institution） ----------------
$router->add('GET',    '/api/leaders',              'LeaderController', 'index',    [$auth, $scope]);
$router->add('POST',   '/api/leaders',              'LeaderController', 'store',    [$auth, $scope]);
$router->add('GET',    '/api/leaders/:id',         'LeaderController', 'show',     [$auth, $scope]);
$router->add('PUT',    '/api/leaders/:id',         'LeaderController', 'update',   [$auth, $scope]);
$router->add('DELETE', '/api/leaders/:id',         'LeaderController', 'destroy',  [$auth, $scope]);
$router->add('PATCH',  '/api/leaders/:id/status',  'LeaderController', 'setStatus',[$auth, $scope]);

// ---------------- Sales（platform|institution，只读+启停） ----------------
$router->add('GET',   '/api/sales',             'SalesController', 'index',    [$auth, $scope]);
$router->add('GET',   '/api/sales/:id',        'SalesController', 'show',     [$auth, $scope]);
$router->add('PATCH', '/api/sales/:id/status', 'SalesController', 'setStatus',[$auth, $scope]);

// ---------------- Settings（platform） ----------------
$router->add('GET', '/api/settings',          'SettingsController', 'index',        [$auth, $scope]);
$router->add('PUT', '/api/settings/domain',   'SettingsController', 'updateDomain', [$auth, $scope]);

// ---------------- Overview（platform|institution） ----------------
$router->add('GET', '/api/overview', 'OverviewController', 'index', [$auth, $scope]);

$router->dispatch();
