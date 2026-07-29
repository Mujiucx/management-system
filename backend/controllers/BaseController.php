<?php
/**
 * controllers/BaseController.php
 * 所有 controller 的公共基类：请求体解析、账户/scope 读取、角色校验。
 */

namespace Controllers;

use Utils\Context;
use Utils\Response;

class BaseController
{
    /**
     * 解析请求体：JSON 解析为数组，否则回退到 $_POST。
     */
    protected function body(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }
        return $_POST ?? [];
    }

    /**
     * 当前登录账户。
     */
    protected function account(): array
    {
        return Context::$auth ?? [];
    }

    /**
     * 当前数据权限 scope。
     */
    protected function scope(): array
    {
        return Context::$scope ?? [];
    }

    /**
     * 要求指定角色，否则 403。
     */
    protected function requireRole(string $role): void
    {
        if (($this->account()['role'] ?? '') !== $role) {
            Response::error(403, '无权限', 403);
        }
    }

    /**
     * 要求平台管理员，否则 403。
     */
    protected function requirePlatform(): void
    {
        $this->requireRole('platform');
    }

    /**
     * 要求角色为 platform 或 institution。
     */
    protected function requirePlatformOrInstitution(): void
    {
        $role = $this->account()['role'] ?? '';
        if (!in_array($role, ['platform', 'institution'], true)) {
            Response::error(403, '无权限', 403);
        }
    }
}
