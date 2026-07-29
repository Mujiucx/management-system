<?php
/**
 * middleware/AuthMiddleware.php
 * 从 Authorization: Bearer <token> 取令牌，校验登录态。无/失效 -> 401。
 */

namespace Middleware;

use Utils\Auth;
use Utils\Context;
use Utils\Response;

class AuthMiddleware
{
    public static function handle(): void
    {
        $token = Auth::getBearerToken();
        $account = Auth::getAccountByToken($token);
        if (!$account) {
            Response::error(401, '未登录或令牌失效', 401);
        }
        Context::$auth = $account;
    }
}
