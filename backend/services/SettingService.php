<?php
/**
 * services/SettingService.php
 * 系统设置读写（含 share_domain）。仅平台管理员可访问。
 */

namespace Services;

use Utils\DB;

class SettingService
{
    public static function getShareDomain(): string
    {
        $row = DB::fetch("SELECT value FROM settings WHERE `key` = 'share_domain'");
        if ($row && !empty($row['value'])) {
            return $row['value'];
        }
        return DEFAULT_SHARE_DOMAIN;
    }

    public static function setShareDomain(string $value): void
    {
        $row = DB::fetch("SELECT id FROM settings WHERE `key` = 'share_domain'");
        if ($row) {
            DB::update('settings', ['value' => $value], ['id' => $row['id']]);
        } else {
            DB::insert('settings', ['key' => 'share_domain', 'value' => $value]);
        }
    }
}
