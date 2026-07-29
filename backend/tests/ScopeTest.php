<?php
/**
 * tests/ScopeTest.php — 数据权限（核心）
 * 覆盖：platform 看全部；institution 仅本机构子树；跨机构/越权 403；机构禁访 settings。
 */

use Tests\TestCase as T;

registerTest('ScopeTest.platform.seesAll', function () {
    $token = loginAs('13800000000');
    $leaders = req('GET', '/api/leaders', ['token' => $token, 'query' => ['page' => 1, 'page_size' => 100]]);
    T::assertOk($leaders, 'platform 列 leaders');
    T::assert(($leaders['body']['total'] ?? 0) >= 1, 'platform 至少看到 1 个团队长');

    $sales = req('GET', '/api/sales', ['token' => $token, 'query' => ['page' => 1, 'page_size' => 100]]);
    T::assertOk($sales, 'platform 列 sales');
    T::assert(($sales['body']['total'] ?? 0) >= 1, 'platform 至少看到 1 个业务员');
});

registerTest('ScopeTest.institution.filtered', function () {
    // 以 platform 建“隔离机构B”+ 其团队长 + 业务员，确保机构隔离可观测
    $ptoken = loginAs('13800000000');
    $org2 = req('POST', '/api/orgs', ['token' => $ptoken, 'body' => [
        'full_name'   => '隔离机构B',
        'short_name'  => '机构B',
        'license_no'  => 'LIC' . uniqid(),
        'contact_name'=> 'B',
        'contact_phone'=> uniqPhone(),
    ]]);
    $org2Id = (int) ($org2['body']['data']['id'] ?? 0);

    $leader2 = req('POST', '/api/leaders', ['token' => $ptoken, 'body' => [
        'name'           => '团队长B',
        'phone'          => uniqPhone(),
        'institution_id' => $org2Id,
    ]]);
    $leader2Id = (int) ($leader2['body']['data']['id'] ?? 0);
    $salesBId = \Utils\DB::insert('sales', [
        'name'      => '业务员B',
        'phone'     => uniqPhone(),
        'leader_id' => $leader2Id,
        'status'    => 'active',
    ]);

    // 机构1 登录
    $itoken = loginAs('13800000001');
    $leaders = req('GET', '/api/leaders', ['token' => $itoken, 'query' => ['page' => 1, 'page_size' => 100]]);
    T::assertOk($leaders, '机构列 leaders');
    $allOwn = true;
    foreach (($leaders['body']['list'] ?? []) as $l) {
        if ((int) $l['institution_id'] !== 1) {
            $allOwn = false;
        }
    }
    T::assert($allOwn, '机构仅看到本机构(institution_id=1)的团队长');
    T::assert((int) ($leaders['body']['total'] ?? 0) === 1, '机构看到的团队长数=1(不含机构B)');

    $sales = req('GET', '/api/sales', ['token' => $itoken, 'query' => ['page' => 1, 'page_size' => 100]]);
    T::assertOk($sales, '机构列 sales');
    $ids = array_column($sales['body']['list'] ?? [], 'id');
    T::assert(!in_array($salesBId, $ids, true), '机构看不到其他机构下属的业务员');
    T::assert((int) ($sales['body']['total'] ?? 0) === 1, '机构看到的业务员数=1(不含机构B)');
});

registerTest('ScopeTest.institution.crossOrgForbidden', function () {
    $itoken = loginAs('13800000001');
    $ptoken = loginAs('13800000000');

    $orgC = req('POST', '/api/orgs', ['token' => $ptoken, 'body' => [
        'full_name'   => '越权机构C',
        'short_name'  => '机构C',
        'license_no'  => 'LIC' . uniqid(),
        'contact_name'=> 'C',
        'contact_phone'=> uniqPhone(),
    ]]);
    $orgCId = (int) ($orgC['body']['data']['id'] ?? 0);
    $cross = req('GET', '/api/orgs/' . $orgCId, ['token' => $itoken]);
    T::assertStatus(403, $cross['status'], '机构越权访问其他机构详情(403)');

    $leaderC = req('POST', '/api/leaders', ['token' => $ptoken, 'body' => [
        'name'           => '团队长C',
        'phone'          => uniqPhone(),
        'institution_id' => $orgCId,
    ]]);
    $leaderCId = (int) ($leaderC['body']['data']['id'] ?? 0);
    $crossL = req('GET', '/api/leaders/' . $leaderCId, ['token' => $itoken]);
    T::assertStatus(403, $crossL['status'], '机构越权访问其他机构团队长(403)');
});

registerTest('ScopeTest.institution.settingsForbidden', function () {
    $itoken = loginAs('13800000001');
    $s = req('GET', '/api/settings', ['token' => $itoken]);
    T::assertStatus(403, $s['status'], '机构访问 settings 返回 403');
});
