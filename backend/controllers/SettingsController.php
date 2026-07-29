<?php
/**
 * controllers/SettingsController.php
 * 系统设置（仅平台管理员）。当前仅维护 share_domain。
 */

namespace Controllers;

use Utils\Response;
use Services\SettingService;

class SettingsController extends BaseController
{
    /**
     * GET /api/settings
     */
    public function index(array $params): void
    {
        $this->requirePlatform();
        Response::ok(['share_domain' => SettingService::getShareDomain()]);
    }

    /**
     * PUT /api/settings/domain
     */
    public function updateDomain(array $params): void
    {
        $this->requirePlatform();
        $b = $this->body();
        $domain = trim($b['share_domain'] ?? $b['domain'] ?? '');
        if ($domain === '') {
            Response::error(400, '分享域名不能为空');
        }
        SettingService::setShareDomain($domain);
        Response::ok(['share_domain' => $domain]);
    }
}
