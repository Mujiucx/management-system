<?php
/**
 * router/Router.php
 * 轻量路由：维护 [method][pattern] -> [Controller, action, middlewares]。
 * 支持 :param 占位；解析当前 method + path，匹配并提取参数后调用 controller action。
 */

namespace Router;

use Utils\Response;

class Router
{
    /** @var array 路由表 */
    private array $routes = [];

    /**
     * 注册路由。
     */
    public function add(
        string $method,
        string $pattern,
        string $controller,
        string $action,
        array $middlewares = []
    ): void {
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'regex' => $this->compile($pattern),
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * 将 /path/:param 编译为正则，命名捕获组 (?P<param>[^/]+)。
     */
    private function compile(string $pattern): string
    {
        $regex = preg_replace_callback(
            '#/:([\w]+)#',
            static fn($m) => '/(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );
        return '#^' . $regex . '$#';
    }

    /**
     * 派发当前请求。
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === false || $path === null || $path === '') {
            $path = '/';
        }

        // 去掉脚本名前缀（兼容 DocumentRoot 为项目根或 public/ 两种情况）
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName !== '' && $scriptName !== '/') {
            $scriptDir = dirname($scriptName);
            if (($scriptDir === '/' || $scriptDir === '\\') && basename($scriptName) === 'index.php') {
                // DocumentRoot 指向 public/，脚本名为 /index.php，无需去除
            } elseif ($scriptDir !== '/' && $scriptDir !== '\\' && strpos($path, $scriptDir) === 0) {
                $path = substr($path, strlen($scriptDir));
            } elseif ($path === $scriptName) {
                $path = '/';
            }
        }

        $path = rtrim($path, '/');
        if ($path === '' || $path === false) {
            $path = '/';
        }

        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = urldecode($v);
                    }
                }
                foreach ($route['middlewares'] as $mw) {
                    call_user_func($mw);
                }
                $ctrlClass = 'Controllers\\' . $route['controller'];
                if (!class_exists($ctrlClass)) {
                    Response::error(500, 'Controller not found: ' . $route['controller'], 500);
                }
                $ctrl = new $ctrlClass();
                if (!method_exists($ctrl, $route['action'])) {
                    Response::error(500, 'Action not found: ' . $route['action'], 500);
                }
                call_user_func([$ctrl, $route['action']], $params);
                return;
            }
        }

        Response::error(404, 'Not Found: ' . $method . ' ' . $path, 404);
    }
}
