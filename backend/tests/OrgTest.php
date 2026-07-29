<?php
/**
 * tests/OrgTest.php — 机构管理（platform）
 * 覆盖：创建并自动建机构管理员账户、列表/详情/编辑/启停、有下属时删除 409。
 */

use Tests\TestCase as T;

registerTest('OrgTest.create.autoAccount', function () {
    $ptoken = loginAs('13800000000');
    $before = \Utils\DB::count('accounts', ['role' => 'institution']);
    $resp = req('POST', '/api/orgs', ['token' => $ptoken, 'body' => [
        'full_name'    => '新建机构D',
        'short_name'   => '机构D',
        'license_no'   => 'LIC' . uniqid(),
        'contact_name' => 'D',
        'contact_phone'=> uniqPhone(),
    ]]);
    T::assertOk($resp, 'platform 创建机构成功');
    $after = \Utils\DB::count('accounts', ['role' => 'institution']);
    T::assert($after === $before + 1, '自动创建机构管理员账户(role=institution) +1');
});

registerTest('OrgTest.crud', function () {
    $ptoken = loginAs('13800000000');
    $list = req('GET', '/api/orgs', ['token' => $ptoken, 'query' => ['page' => 1, 'page_size' => 100]]);
    T::assertOk($list, '机构列表');
    T::assert(($list['body']['total'] ?? 0) >= 1, '列表至少 1 条');

    $id = (int) ($list['body']['list'][0]['id'] ?? 0);
    $detail = req('GET', '/api/orgs/' . $id, ['token' => $ptoken]);
    T::assertOk($detail, '机构详情');

    $upd = req('PUT', '/api/orgs/' . $id, ['token' => $ptoken, 'body' => ['contact_name' => '改后名']]);
    T::assertOk($upd, '机构编辑');
    T::assert(($upd['body']['data']['contact_name'] ?? '') === '改后名', '编辑生效');

    $st = req('PATCH', '/api/orgs/' . $id . '/status', ['token' => $ptoken, 'body' => ['status' => 'disabled']]);
    T::assertOk($st, '机构启停');
    T::assert(($st['body']['data']['status'] ?? '') === 'disabled', '状态变为 disabled');
    // 还原为 active，避免影响后续
    req('PATCH', '/api/orgs/' . $id . '/status', ['token' => $ptoken, 'body' => ['status' => 'active']]);
});

registerTest('OrgTest.deleteWithSubordinates', function () {
    $ptoken = loginAs('13800000000');
    // 机构1 下存在团队长1，删除应 409
    $resp = req('DELETE', '/api/orgs/1', ['token' => $ptoken]);
    T::assertStatus(409, $resp['status'], '机构有下属团队长时删除返回 409');
});
