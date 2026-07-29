<?php
/**
 * controllers/LeaderController.php
 * 团队长管理（平台 + 机构）。机构限本机构。
 */

namespace Controllers;

use Utils\Auth;
use Utils\DB;
use Utils\Response;
use Services\LeaderService;

class LeaderController extends BaseController
{
    /**
     * GET /api/leaders
     */
    public function index(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['page_size'] ?? 20)));
        $res = LeaderService::list($_GET, $this->scope(), $page, $pageSize);
        Response::listResp($res['list'], $res['total'], $page, $pageSize);
    }

    /**
     * POST /api/leaders  （自动建账户 + 生成 qr_link）
     */
    public function store(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $b = $this->body();
        $name = trim($b['name'] ?? '');
        $phone = trim($b['phone'] ?? '');
        $institutionId = $this->scope()['type'] === 'institution'
            ? (int) $this->scope()['institution_id']
            : (int) ($b['institution_id'] ?? 0);
        $nickname = trim($b['nickname'] ?? '');
        $leaderCode = trim($b['leader_code'] ?? '');
        $password = $b['password'] ?? 'admin123';

        if ($name === '' || $phone === '' || $institutionId <= 0) {
            Response::error(400, '姓名、手机号、所属机构为必填');
        }
        if (!DB::fetch('SELECT id FROM institutions WHERE id = ?', [$institutionId])) {
            Response::error(400, '所属机构不存在');
        }
        if (DB::count('leaders', ['phone' => $phone]) > 0) {
            Response::error(409, '手机号已存在');
        }
        if (DB::count('accounts', ['phone' => $phone]) > 0) {
            Response::error(409, '手机号已注册为账号');
        }

        $leader = LeaderService::create([
            'name' => $name,
            'nickname' => $nickname,
            'phone' => $phone,
            'institution_id' => $institutionId,
            'leader_code' => $leaderCode,
        ], $password);

        Response::ok($leader);
    }

    /**
     * GET /api/leaders/:id
     */
    public function show(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $id = (int) ($params['id'] ?? 0);
        $leader = LeaderService::detail($id);
        if (!$leader) {
            Response::error(404, '团队长不存在');
        }
        $this->checkScope($leader);
        Response::ok($leader);
    }

    /**
     * PUT /api/leaders/:id
     */
    public function update(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $id = (int) ($params['id'] ?? 0);
        $leader = DB::fetch('SELECT * FROM leaders WHERE id = ?', [$id]);
        if (!$leader) {
            Response::error(404, '团队长不存在');
        }
        $this->checkScope($leader);

        $b = $this->body();
        $data = [];
        foreach (['name', 'nickname'] as $f) {
            if (array_key_exists($f, $b)) {
                $data[$f] = $b[$f] === '' ? null : $b[$f];
            }
        }
        if (array_key_exists('phone', $b) && $b['phone'] !== $leader['phone']) {
            $phone = trim($b['phone']);
            if (DB::count('leaders', ['phone' => $phone]) > 0) {
                Response::error(409, '手机号已存在');
            }
            if (DB::count('accounts', ['phone' => $phone]) > 0) {
                Response::error(409, '手机号已注册为账号');
            }
            $data['phone'] = $phone;
        }
        if ($this->scope()['type'] === 'platform' && array_key_exists('institution_id', $b)) {
            $data['institution_id'] = (int) $b['institution_id'];
        }

        if (!empty($data)) {
            DB::update('leaders', $data, ['id' => $id]);
        }
        if (isset($data['phone'])) {
            DB::update('accounts', ['phone' => $data['phone']], ['ref_type' => 'leader', 'ref_id' => $id]);
        }
        if (isset($data['institution_id'])) {
            DB::update('accounts', ['institution_id' => $data['institution_id']], ['ref_type' => 'leader', 'ref_id' => $id]);
        }

        $leader = DB::fetch('SELECT * FROM leaders WHERE id = ?', [$id]);
        Response::ok($leader);
    }

    /**
     * DELETE /api/leaders/:id  （有下属业务员禁止硬删）
     */
    public function destroy(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $id = (int) ($params['id'] ?? 0);
        $leader = DB::fetch('SELECT * FROM leaders WHERE id = ?', [$id]);
        if (!$leader) {
            Response::error(404, '团队长不存在');
        }
        $this->checkScope($leader);
        if (DB::count('sales', ['leader_id' => $id]) > 0) {
            Response::error(409, '该团队长下存在业务员，禁止删除');
        }
        DB::delete('accounts', ['ref_type' => 'leader', 'ref_id' => $id]);
        DB::delete('leaders', ['id' => $id]);
        Response::ok(null);
    }

    /**
     * PATCH /api/leaders/:id/status
     */
    public function setStatus(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $id = (int) ($params['id'] ?? 0);
        $leader = DB::fetch('SELECT * FROM leaders WHERE id = ?', [$id]);
        if (!$leader) {
            Response::error(404, '团队长不存在');
        }
        $this->checkScope($leader);
        $b = $this->body();
        $status = $b['status'] ?? '';
        if (!in_array($status, ['active', 'disabled'], true)) {
            Response::error(400, '状态值非法');
        }
        DB::update('leaders', ['status' => $status], ['id' => $id]);
        DB::update('accounts', ['status' => $status], ['ref_type' => 'leader', 'ref_id' => $id]);
        Response::ok(['id' => $id, 'status' => $status]);
    }

    /**
     * 校验机构级数据权限。
     */
    private function checkScope(array $leader): void
    {
        $scope = $this->scope();
        if ($scope['type'] === 'institution' && (int) $leader['institution_id'] !== $scope['institution_id']) {
            Response::error(403, '无权限访问该团队长');
        }
    }
}
