<?php
/**
 * controllers/OrgController.php
 * 机构管理（仅平台管理员）。含创建机构自动建账户、上传营业执照。
 */

namespace Controllers;

use Utils\Auth;
use Utils\DB;
use Utils\Response;
use Utils\Upload;
use Services\InstitutionService;

class OrgController extends BaseController
{
    /**
     * GET /api/orgs
     */
    public function index(array $params): void
    {
        $this->requirePlatform();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['page_size'] ?? 20)));
        $res = InstitutionService::list($_GET, $page, $pageSize);
        Response::listResp($res['list'], $res['total'], $page, $pageSize);
    }

    /**
     * POST /api/orgs  （自动建机构管理员账户，账号=contact_phone）
     */
    public function store(array $params): void
    {
        $this->requirePlatform();
        $b = $this->body();
        $fullName = trim($b['full_name'] ?? '');
        $shortName = trim($b['short_name'] ?? '');
        $licenseNo = trim($b['license_no'] ?? '');
        $contactName = trim($b['contact_name'] ?? '');
        $contactPhone = trim($b['contact_phone'] ?? '');
        $licensePath = trim($b['license_path'] ?? '');
        $adminPassword = $b['admin_password'] ?? 'admin123';

        if ($fullName === '' || $shortName === '' || $licenseNo === '' || $contactPhone === '') {
            Response::error(400, '企业全称、简称、统一社会信用代码、联系人手机号为必填');
        }
        if (DB::count('institutions', ['license_no' => $licenseNo]) > 0) {
            Response::error(409, '统一社会信用代码已存在');
        }
        if (DB::count('accounts', ['phone' => $contactPhone]) > 0) {
            Response::error(409, '联系人手机号已注册为账号');
        }

        $orgId = DB::insert('institutions', [
            'full_name' => $fullName,
            'short_name' => $shortName,
            'license_no' => $licenseNo,
            'license_path' => $licensePath ?: null,
            'contact_name' => $contactName ?: null,
            'contact_phone' => $contactPhone,
            'status' => 'active',
        ]);

        DB::insert('accounts', [
            'role' => 'institution',
            'phone' => $contactPhone,
            'password' => Auth::hashPassword($adminPassword),
            'status' => 'active',
            'institution_id' => $orgId,
            'ref_type' => 'institution',
            'ref_id' => $orgId,
        ]);

        $org = DB::fetch('SELECT * FROM institutions WHERE id = ?', [$orgId]);
        Response::ok($org);
    }

    /**
     * GET /api/orgs/:id  （含下属团队长/业务员数量下钻）
     */
    public function show(array $params): void
    {
        $this->requirePlatform();
        $id = (int) ($params['id'] ?? 0);
        $org = InstitutionService::detail($id);
        if (!$org) {
            Response::error(404, '机构不存在');
        }
        Response::ok($org);
    }

    /**
     * PUT /api/orgs/:id
     */
    public function update(array $params): void
    {
        $this->requirePlatform();
        $id = (int) ($params['id'] ?? 0);
        $org = DB::fetch('SELECT * FROM institutions WHERE id = ?', [$id]);
        if (!$org) {
            Response::error(404, '机构不存在');
        }
        $b = $this->body();
        $data = [];
        foreach (['full_name', 'short_name', 'license_no', 'license_path', 'contact_name', 'contact_phone'] as $f) {
            if (array_key_exists($f, $b)) {
                $data[$f] = $b[$f] === '' ? null : $b[$f];
            }
        }
        if (isset($data['license_no']) && $data['license_no'] !== $org['license_no']) {
            if (DB::count('institutions', ['license_no' => $data['license_no']]) > 0) {
                Response::error(409, '统一社会信用代码已存在');
            }
        }
        // 先校验联系人手机号未被「其他」账户占用，校验通过后再执行任何写入，
        // 避免先改了机构手机号、却又因手机号冲突返回 409 导致的部分写入。
        if (isset($data['contact_phone']) && $data['contact_phone'] !== $org['contact_phone']) {
            if (DB::count('accounts', ['phone' => $data['contact_phone']]) > 0) {
                Response::error(409, '联系人手机号已注册为账号');
            }
        }
        if (!empty($data)) {
            DB::update('institutions', $data, ['id' => $id]);
        }
        // 校验已通过，安全更新机构管理员账户手机号
        if (isset($data['contact_phone']) && $data['contact_phone'] !== $org['contact_phone']) {
            DB::update('accounts', ['phone' => $data['contact_phone']], ['ref_type' => 'institution', 'ref_id' => $id]);
        }
        $org = DB::fetch('SELECT * FROM institutions WHERE id = ?', [$id]);
        Response::ok($org);
    }

    /**
     * DELETE /api/orgs/:id  （有下属团队长禁止硬删）
     */
    public function destroy(array $params): void
    {
        $this->requirePlatform();
        $id = (int) ($params['id'] ?? 0);
        if (DB::count('leaders', ['institution_id' => $id]) > 0) {
            Response::error(409, '该机构下存在团队长，禁止删除');
        }
        DB::delete('accounts', ['ref_type' => 'institution', 'ref_id' => $id]);
        DB::delete('institutions', ['id' => $id]);
        Response::ok(null);
    }

    /**
     * PATCH /api/orgs/:id/status
     */
    public function setStatus(array $params): void
    {
        $this->requirePlatform();
        $id = (int) ($params['id'] ?? 0);
        $org = DB::fetch('SELECT * FROM institutions WHERE id = ?', [$id]);
        if (!$org) {
            Response::error(404, '机构不存在');
        }
        $b = $this->body();
        $status = $b['status'] ?? '';
        if (!in_array($status, ['active', 'disabled'], true)) {
            Response::error(400, '状态值非法');
        }
        DB::update('institutions', ['status' => $status], ['id' => $id]);
        DB::update('accounts', ['status' => $status], ['ref_type' => 'institution', 'ref_id' => $id]);
        Response::ok(['id' => $id, 'status' => $status]);
    }

    /**
     * POST /api/upload  （需登录；保存营业执照等）
     */
    public function upload(array $params): void
    {
        if (empty($_FILES['file'])) {
            Response::error(400, '未收到上传文件');
        }
        $module = trim($_POST['module'] ?? 'org');
        try {
            $url = Upload::saveUpload($_FILES['file'], $module);
        } catch (\Exception $e) {
            Response::error(400, $e->getMessage());
        }
        Response::ok(['url' => $url]);
    }
}
