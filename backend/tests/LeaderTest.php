<?php
/**
 * tests/LeaderTest.php — 团队长管理（platform|institution）
 * 覆盖：创建+自动建账户+qr_link；机构强制本机构；启停；有下属业务员时删除 409。
 */

use Tests\TestCase as T;

registerTest('LeaderTest.create.autoAccountAndQr', function () {
    $ptoken = loginAs('13800000000');
    $resp = req('POST', '/api/leaders', ['token' => $ptoken, 'body' => [
        'name'           => '新建团队长E',
        'phone'          => uniqPhone(),
        'institution_id' => 1,
    ]]);
    T::assertOk($resp, 'platform 创建团队长成功');
    T::assert(!empty($resp['body']['data']['id']), '返回 id');
    T::assert(!empty($resp['body']['data']['qr_link']), '返回 qr_link');
    T::assert(preg_match('#/invite\?id=\d+$#', (string) $resp['body']['data']['qr_link']) === 1, 'qr_link 形如 域名/invite?id=<id>');

    $leaderId = (int) ($resp['body']['data']['id'] ?? 0);
    $acc = \Utils\DB::count('accounts', ['role' => 'leader', 'ref_id' => $leaderId]);
    T::assert($acc === 1, '自动建 leader 账户(ref_id=leaderId)');
});

registerTest('LeaderTest.institution.forcedInstitution', function () {
    $itoken = loginAs('13800000001');
    // 试图以其他机构 id 创建，应被强制为本机构
    $resp = req('POST', '/api/leaders', ['token' => $itoken, 'body' => [
        'name'           => '越权团队长F',
        'phone'          => uniqPhone(),
        'institution_id' => 999,
    ]]);
    T::assertOk($resp, '机构创建团队长成功');
    T::assert((int) ($resp['body']['data']['institution_id'] ?? 0) === 1, 'institution_id 被强制为本机构(1)');
});

registerTest('LeaderTest.statusToggle', function () {
    $ptoken = loginAs('13800000000');
    $st = req('PATCH', '/api/leaders/1/status', ['token' => $ptoken, 'body' => ['status' => 'disabled']]);
    T::assertOk($st, '团队长启停');
    T::assert(($st['body']['data']['status'] ?? '') === 'disabled', '状态变为 disabled');
    req('PATCH', '/api/leaders/1/status', ['token' => $ptoken, 'body' => ['status' => 'active']]);
});

registerTest('LeaderTest.deleteWithSales', function () {
    $ptoken = loginAs('13800000000');
    // 团队长1 下存在业务员1，删除应 409
    $resp = req('DELETE', '/api/leaders/1', ['token' => $ptoken]);
    T::assertStatus(409, $resp['status'], '团队长有下属业务员时删除返回 409');
});
