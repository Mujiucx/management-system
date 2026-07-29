<?php
/**
 * utils/sms.php
 * 模拟短信验证码：生成 6 位码，password_hash 存入 sms_codes，5 分钟有效。
 * 开发态可由 sendCode 返回明文码用于演示。
 */

namespace Utils;

use Utils\DB;

class Sms
{
    /**
     * 生成并存储验证码，返回明文码（供 DEV 模式回传）。
     */
    public static function sendCode(string $phone): string
    {
        $code = (string) random_int(100000, 999999);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 MINUTE'));
        DB::insert('sms_codes', [
            'phone' => $phone,
            'code_hash' => $codeHash,
            'expires_at' => $expiresAt,
            'used' => 0,
        ]);
        return $code;
    }

    /**
     * 校验验证码：比对未过期且未 used 的记录，成功后置 used=1。
     */
    public static function verifyCode(string $phone, string $code): bool
    {
        $rows = DB::fetchAll(
            'SELECT * FROM sms_codes WHERE phone = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC',
            [$phone]
        );
        foreach ($rows as $row) {
            if (password_verify($code, $row['code_hash'])) {
                DB::update('sms_codes', ['used' => 1], ['id' => $row['id']]);
                return true;
            }
        }
        return false;
    }
}
