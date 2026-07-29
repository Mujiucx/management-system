<?php
/**
 * tests/AuthTest.php — 鉴权模块
 * 覆盖：密码登录、密码错误、发送验证码(DEV)、验证码登录、登出失效。
 */

use Tests\TestCase as T;

registerTest('AuthTest.login.success', function () {
    $resp = req('POST', '/api/auth/login', ['body' => ['phone' => '13800000000', 'password' => 'admin123']]);
    T::assertOk($resp, '密码登录成功(200/code0)');
    T::assert(!empty($resp['body']['data']['token']), '登录返回 token');
    T::assert(($resp['body']['data']['account']['role'] ?? '') === 'platform', '返回 account.role=platform');
});

registerTest('AuthTest.login.wrongPassword', function () {
    $resp = req('POST', '/api/auth/login', ['body' => ['phone' => '13800000000', 'password' => 'wrong-password']]);
    // 需求：密码错误应返回 401（与「令牌失效」一致）。当前实现返回 400 —— 见 QA 报告(源码 bug)。
    T::assertStatus(401, $resp['status'], '密码错误返回 401');
});

registerTest('AuthTest.sendCode.dev', function () {
    $resp = req('POST', '/api/auth/send-code', ['body' => ['phone' => '13800000000']]);
    T::assertOk($resp, 'send-code 成功');
    $code = $resp['body']['data']['code'] ?? '';
    T::assert(preg_match('/^\d{6}$/', (string) $code) === 1, 'DEV 模式 data.code 为 6 位数字');
});

registerTest('AuthTest.loginSms.success', function () {
    $codeResp = req('POST', '/api/auth/send-code', ['body' => ['phone' => '13800000000']]);
    $code = $codeResp['body']['data']['code'] ?? '';
    $resp = req('POST', '/api/auth/login-sms', ['body' => ['phone' => '13800000000', 'code' => $code]]);
    T::assertOk($resp, '验证码登录成功(200/code0)');
    T::assert(($resp['body']['data']['account']['role'] ?? '') === 'platform', '验证码登录 account.role=platform');
});

registerTest('AuthTest.logout.invalidate', function () {
    $token = loginAs('13800000000');
    T::assert(!empty($token), '登录获取 token');
    $logout = req('POST', '/api/auth/logout', ['token' => $token]);
    T::assertOk($logout, '登出成功');
    // 复用旧 token 访问需鉴权接口，应 401
    $after = req('GET', '/api/orgs', ['token' => $token]);
    T::assertStatus(401, $after['status'], '登出后旧 token 失效(401)');
});
