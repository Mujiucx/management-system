<?php
/**
 * utils/auth.php
 * 密码哈希、令牌生成、会话（sessions 表）管理、Bearer 令牌解析。
 */

namespace Utils;

use Utils\DB;

class Auth
{
    /**
     * 哈希密码。
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 校验密码。
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        if (empty($hash)) {
            return false;
        }
        return password_verify($password, $hash);
    }

    /**
     * 生成 64 位十六进制随机令牌。
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * 创建会话，写 sessions 表，返回 token。有效期 7 天。
     */
    public static function createSession(int $accountId): string
    {
        $token = self::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . TOKEN_TTL_DAYS . ' DAY'));
        DB::insert('sessions', [
            'token' => $token,
            'account_id' => $accountId,
            'expires_at' => $expiresAt,
        ]);
        return $token;
    }

    /**
     * 销毁会话（登出）。
     */
    public static function destroySession(string $token): void
    {
        if ($token !== '') {
            DB::delete('sessions', ['token' => $token]);
        }
    }

    /**
     * 通过 token 解析登录账户，校验存在且未过期。返回 account 或 null。
     */
    public static function getAccountByToken(?string $token): ?array
    {
        if (!$token) {
            return null;
        }
        $session = DB::fetch('SELECT * FROM sessions WHERE token = ?', [$token]);
        if (!$session) {
            return null;
        }
        if (strtotime($session['expires_at']) < time()) {
            DB::delete('sessions', ['token' => $token]);
            return null;
        }
        $account = DB::fetch('SELECT * FROM accounts WHERE id = ?', [$session['account_id']]);
        return $account ?: null;
    }

    /**
     * 从 Authorization: Bearer <token> 头解析 token。
     */
    public static function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }
}
