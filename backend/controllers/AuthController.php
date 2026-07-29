<?php
/**
 * controllers/AuthController.php
 * 登录（密码/验证码）、发送验证码、登出。
 */

namespace Controllers;

use Utils\Auth;
use Utils\Sms;
use Utils\Response;
use Utils\DB;

class AuthController extends BaseController
{
    /**
     * POST /api/auth/login
     */
    public function login(array $params): void
    {
        $b = $this->body();
        $phone = trim($b['phone'] ?? '');
        $password = $b['password'] ?? '';
        if ($phone === '' || $password === '') {
            Response::error(400, '手机号与密码必填');
        }
        $account = DB::fetch('SELECT * FROM accounts WHERE phone = ?', [$phone]);
        if (!$account || empty($account['password']) || !Auth::verifyPassword($password, $account['password'])) {
            Response::error(401, '手机号或密码错误', 401);
        }
        if ($account['status'] !== 'active') {
            Response::error(403, '账号已被禁用');
        }
        $token = Auth::createSession($account['id']);
        Response::ok([
            'token' => $token,
            'account' => $this->accountView($account),
        ]);
    }

    /**
     * POST /api/auth/send-code  （模拟发送验证码，DEV 返回明文）
     */
    public function sendCode(array $params): void
    {
        $b = $this->body();
        $phone = trim($b['phone'] ?? '');
        if (!preg_match('/^1\d{10}$/', $phone)) {
            Response::error(400, '手机号格式不正确');
        }
        $code = Sms::sendCode($phone);
        if (DEV_MODE) {
            Response::ok(['code' => $code]);
        } else {
            Response::ok(null);
        }
    }

    /**
     * POST /api/auth/login-sms  （验证码登录）
     */
    public function loginSms(array $params): void
    {
        $b = $this->body();
        $phone = trim($b['phone'] ?? '');
        $code = trim($b['code'] ?? '');
        if ($phone === '' || $code === '') {
            Response::error(400, '手机号与验证码必填');
        }
        if (!Sms::verifyCode($phone, $code)) {
            Response::error(401, '验证码错误或已失效', 401);
        }
        $account = DB::fetch('SELECT * FROM accounts WHERE phone = ?', [$phone]);
        if (!$account) {
            Response::error(401, '账号不存在', 401);
        }
        if ($account['status'] !== 'active') {
            Response::error(403, '账号已被禁用');
        }
        $token = Auth::createSession($account['id']);
        Response::ok([
            'token' => $token,
            'account' => $this->accountView($account),
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(array $params): void
    {
        $token = Auth::getBearerToken();
        if ($token) {
            Auth::destroySession($token);
        }
        Response::ok(null);
    }

    /**
     * 统一账户视图。
     */
    private function accountView(array $account): array
    {
        return [
            'id' => (int) $account['id'],
            'role' => $account['role'],
            'phone' => $account['phone'],
            'status' => $account['status'],
            'institution_id' => $account['institution_id'] ? (int) $account['institution_id'] : null,
            'leader_id' => $account['leader_id'] ? (int) $account['leader_id'] : null,
        ];
    }
}
