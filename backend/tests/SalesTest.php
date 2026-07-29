<?php
/**
 * tests/SalesTest.php — 业务员（只读 + 启停）
 * 覆盖：列表/详情+scope 过滤；PATCH 启停；无创建/删除端点(路由未注册 -> 404)。
 */

use Tests\TestCase as T;

registerTest('SalesTest.listDetailAndScope', function () {
    $itoken = loginAs('13800000001');
    $list = req('GET', '/api/sales', ['token' => $itoken, 'query' => ['page' => 1, 'page_size' => 100]]);
    T::assertOk($list, '机构列 sales');
    T::assert(($list['body']['total'] ?? 0) >= 1, '机构至少看到 1 个业务员');

    $id = (int) ($list['body']['list'][0]['id'] ?? 0);
    $detail = req('GET', '/api/sales/' . $id, ['token' => $itoken]);
    T::assertOk($detail, '业务员详情');

    $ptoken = loginAs('13800000000');
    $plist = req('GET', '/api/sales', ['token' => $ptoken, 'query' => ['page' => 1, 'page_size' => 100]]);
    T::assertOk($plist, '平台列 sales');
});

registerTest('SalesTest.statusToggle', function () {
    $ptoken = loginAs('13800000000');
    $st = req('PATCH', '/api/sales/1/status', ['token' => $ptoken, 'body' => ['status' => 'disabled']]);
    T::assertOk($st, '业务员启停');
    T::assert(($st['body']['data']['status'] ?? '') === 'disabled', '状态变为 disabled');
    req('PATCH', '/api/sales/1/status', ['token' => $ptoken, 'body' => ['status' => 'active']]);
});

registerTest('SalesTest.noCreateDeleteEndpoints', function () {
    $ptoken = loginAs('13800000000');
    $create = req('POST', '/api/sales', ['token' => $ptoken, 'body' => ['name' => 'x', 'phone' => uniqPhone(), 'leader_id' => 1]]);
    T::assertStatus(404, $create['status'], 'POST /api/sales 未注册 -> 404');

    $del = req('DELETE', '/api/sales/1', ['token' => $ptoken]);
    T::assertStatus(404, $del['status'], 'DELETE /api/sales/:id 未注册 -> 404');
});
