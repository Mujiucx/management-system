<?php
/**
 * tests/SettingsTest.php — 系统设置（platform）
 * 覆盖：GET 返回 share_domain；platform PUT 更新；institution PUT 返回 403。
 */

use Tests\TestCase as T;

registerTest('SettingsTest.getShareDomain', function () {
    $ptoken = loginAs('13800000000');
    $resp = req('GET', '/api/settings', ['token' => $ptoken]);
    T::assertOk($resp, 'GET settings');
    T::assert(isset($resp['body']['data']['share_domain']), '返回 share_domain 字段');
});

registerTest('SettingsTest.platformUpdate', function () {
    $ptoken = loginAs('13800000000');
    $resp = req('PUT', '/api/settings/domain', ['token' => $ptoken, 'body' => ['share_domain' => 'https://example.com']]);
    T::assertOk($resp, 'platform 更新 domain');
    T::assert(($resp['body']['data']['share_domain'] ?? '') === 'https://example.com', 'domain 已更新');

    // 还原
    req('PUT', '/api/settings/domain', ['token' => $ptoken, 'body' => ['share_domain' => 'http://localhost:8000']]);
});

registerTest('SettingsTest.institutionForbidden', function () {
    $itoken = loginAs('13800000001');
    $resp = req('PUT', '/api/settings/domain', ['token' => $itoken, 'body' => ['share_domain' => 'https://evil.com']]);
    T::assertStatus(403, $resp['status'], '机构 PUT settings 返回 403');
});
