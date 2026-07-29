#!/usr/bin/env php
<?php
/**
 * db/seed.php
 * CLI 脚本：php db/seed.php
 * 1) 执行 db/schema.sql 建表
 * 2) 插入演示数据（幂等）
 *
 * 演示账号（密码均为 admin123）：
 *   平台管理员   13800000000
 *   机构管理员   13800000001  （机构「示例科技」）
 *   团队长       13800000002  （归属机构1，邀请码 L0001）
 *   业务员       13800000003  （归属团队长1）
 */

declare(strict_types=1);

$root = dirname(__DIR__);
define('ROOT_DIR', $root);

require_once $root . '/config/config.php';
require_once $root . '/config/db.php';
require_once $root . '/utils/db.php';
require_once $root . '/utils/auth.php';

$pdo = getPDO();

// 1) 建表（按 ; 切分多语句，逐条执行）
$schema = (string) file_get_contents($root . '/db/schema.sql');
$statements = array_filter(array_map('trim', explode(';', $schema)), static fn($s) => $s !== '');
foreach ($statements as $stmt) {
    $pdo->exec($stmt);
}
echo "Tables ready (" . count($statements) . " statements).\n";

// 2) 平台管理员
$platformPhone = '13800000000';
if (!DB::fetch('SELECT id FROM accounts WHERE phone = ?', [$platformPhone])) {
    DB::insert('accounts', [
        'role' => 'platform',
        'phone' => $platformPhone,
        'password' => Auth::hashPassword('admin123'),
        'status' => 'active',
        'ref_type' => 'platform',
        'ref_id' => 0,
    ]);
    echo "Platform admin created: {$platformPhone} / admin123\n";
}

// 3) 机构 + 机构管理员
$instPhone = '13800000001';
if (!DB::fetch('SELECT id FROM institutions WHERE license_no = ?', ['TEST123456789'])) {
    $instId = DB::insert('institutions', [
        'full_name' => '示例科技有限公司',
        'short_name' => '示例科技',
        'license_no' => 'TEST123456789',
        'contact_name' => '张三',
        'contact_phone' => $instPhone,
        'status' => 'active',
    ]);
    DB::insert('accounts', [
        'role' => 'institution',
        'phone' => $instPhone,
        'password' => Auth::hashPassword('admin123'),
        'status' => 'active',
        'institution_id' => $instId,
        'ref_type' => 'institution',
        'ref_id' => $instId,
    ]);
    echo "Institution #{$instId} + admin created: {$instPhone}\n";
}

// 4) 团队长 + 账户
$leaderPhone = '13800000002';
if (!DB::fetch('SELECT id FROM leaders WHERE phone = ?', [$leaderPhone])) {
    $instId = (int) DB::fetch('SELECT id FROM institutions LIMIT 1')['id'];
    $leaderId = DB::insert('leaders', [
        'name' => '李团队',
        'nickname' => 'Leader Li',
        'phone' => $leaderPhone,
        'institution_id' => $instId,
        'status' => 'active',
        'leader_code' => 'L0001',
        'qr_link' => DEFAULT_SHARE_DOMAIN . '/invite?id=0',
    ]);
    DB::update('leaders', ['qr_link' => DEFAULT_SHARE_DOMAIN . '/invite?id=' . $leaderId], ['id' => $leaderId]);
    DB::insert('accounts', [
        'role' => 'leader',
        'phone' => $leaderPhone,
        'password' => Auth::hashPassword('admin123'),
        'status' => 'active',
        'institution_id' => $instId,
        'leader_id' => $leaderId,
        'ref_type' => 'leader',
        'ref_id' => $leaderId,
    ]);
    echo "Leader #{$leaderId} + account created: {$leaderPhone}\n";
}

// 5) 业务员
$salesPhone = '13800000003';
if (!DB::fetch('SELECT id FROM sales WHERE phone = ?', [$salesPhone])) {
    $leaderId = (int) DB::fetch('SELECT id FROM leaders LIMIT 1')['id'];
    DB::insert('sales', [
        'name' => '王业务',
        'phone' => $salesPhone,
        'leader_id' => $leaderId,
        'status' => 'active',
        'bound_customers' => 0,
        'monthly_performance' => 0.00,
    ]);
    echo "Sales created: {$salesPhone}\n";
}

// 6) 系统设置
if (!DB::fetch("SELECT id FROM settings WHERE `key` = 'share_domain'")) {
    DB::insert('settings', ['key' => 'share_domain', 'value' => 'http://localhost:8000']);
    echo "Settings (share_domain) created.\n";
}

echo "Seed completed.\n";
