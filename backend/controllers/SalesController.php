<?php
/**
 * controllers/SalesController.php
 * 业务员（只读 + 启停）。平台 + 机构（限 scope）。
 */

namespace Controllers;

use Utils\DB;
use Utils\Response;
use Services\SalesService;

class SalesController extends BaseController
{
    /**
     * GET /api/sales
     */
    public function index(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($_GET['page_size'] ?? 20)));
        $res = SalesService::list($_GET, $this->scope(), $page, $pageSize);
        Response::listResp($res['list'], $res['total'], $page, $pageSize);
    }

    /**
     * GET /api/sales/:id
     */
    public function show(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $id = (int) ($params['id'] ?? 0);
        $sales = SalesService::detail($id);
        if (!$sales) {
            Response::error(404, '业务员不存在');
        }
        $this->checkScope($sales);
        Response::ok($sales);
    }

    /**
     * PATCH /api/sales/:id/status  （唯一写操作）
     */
    public function setStatus(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $id = (int) ($params['id'] ?? 0);
        $sales = DB::fetch('SELECT * FROM sales WHERE id = ?', [$id]);
        if (!$sales) {
            Response::error(404, '业务员不存在');
        }
        $this->checkScope($sales);
        $b = $this->body();
        $status = $b['status'] ?? '';
        if (!in_array($status, ['active', 'disabled'], true)) {
            Response::error(400, '状态值非法');
        }
        DB::update('sales', ['status' => $status], ['id' => $id]);
        DB::update('accounts', ['status' => $status], ['ref_type' => 'sales', 'ref_id' => $id]);
        Response::ok(['id' => $id, 'status' => $status]);
    }

    /**
     * 校验机构级数据权限（按下属 leader_ids）。
     */
    private function checkScope(array $sales): void
    {
        $scope = $this->scope();
        if ($scope['type'] === 'institution') {
            $leaderIds = $scope['leader_ids'] ?? [];
            if (!in_array((int) $sales['leader_id'], $leaderIds, true)) {
                Response::error(403, '无权限访问该业务员');
            }
        }
    }
}
