<?php
/**
 * tests/run.php — 依次执行全部测试并汇总。
 *
 * 用法：
 *   cd backend
 *   php db/seed.php        # 可选；run.php 会自动重置并 seed
 *   php tests/run.php
 *
 * 前置：
 *   - 已创建 MySQL 库 agency_admin：CREATE DATABASE agency_admin DEFAULT CHARSET utf8mb4;
 *   - config/config.php 已指向该库（DB_HOST/DB_NAME/DB_USER/DB_PASS）
 *   - PHP >= 8.1 且在 PATH 中（run.php 会启动内置服务器跑集成测试）
 *
 * 退出码：0 = 全部通过；1 = 存在失败。
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

echo "== 重置测试库 ==\n";
resetTestDb();

echo "== 启动内置服务器 (" . SERVER_ADDR . ") ==\n";
if (!startServer()) {
    echo "服务器启动失败，无法运行集成测试。请确认 php 在 PATH 中且端口可用。\n";
    exit(1);
}

// 载入各模块测试
foreach (['AuthTest', 'ScopeTest', 'OrgTest', 'LeaderTest', 'SalesTest', 'SettingsTest'] as $m) {
    require_once __DIR__ . '/' . $m . '.php';
}

echo "\n== 运行测试 ==\n";
[$pass, $fail] = runAllTests();

stopServer();

echo "\n==============================\n";
echo "通过 {$pass} / 失败 {$fail}\n";
if ($fail > 0) {
    echo "失败项：\n - " . implode("\n - ", \Tests\TestCase::$failures) . "\n";
}
echo "==============================\n";
exit($fail > 0 ? 1 : 0);
