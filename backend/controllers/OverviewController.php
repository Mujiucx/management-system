<?php
/**
 * controllers/OverviewController.php
 * 概览统计：机构数 / 团队长数 / 业务员数 / 本月业绩(恒为0，来自H5) + 组织层级树。
 * 按 scope 过滤。平台 / 机构可见。
 */

namespace Controllers;

use Utils\DB;
use Utils\Response;

class OverviewController extends BaseController
{
    public function index(array $params): void
    {
        $this->requirePlatformOrInstitution();
        $scope = $this->scope();

        if ($scope['type'] === 'platform') {
            $orgCount = DB::count('institutions');
            $leaderCount = DB::count('leaders');
            $salesCount = DB::count('sales');
            $tree = $this->buildTree(0);
        } else {
            $institutionId = (int) $scope['institution_id'];
            $orgCount = 1;
            $leaderCount = DB::count('leaders', ['institution_id' => $institutionId]);
            $leaderIds = $scope['leader_ids'] ?? [];
            if ($leaderIds) {
                $ph = implode(',', array_fill(0, count($leaderIds), '?'));
                $salesCount = (int) DB::fetch("SELECT COUNT(*) AS c FROM sales WHERE leader_id IN ($ph)", $leaderIds)['c'];
            } else {
                $salesCount = 0;
            }
            $tree = $this->buildTree($institutionId);
        }

        Response::ok([
            'org_count' => $orgCount,
            'leader_count' => $leaderCount,
            'sales_count' => $salesCount,
            'monthly_performance' => 0,
            'tree' => $tree,
        ]);
    }

    /**
     * 构建组织层级树（机构 -> 团队长 -> 业务员数量）。
     */
    private function buildTree(int $limitInstitution = 0): array
    {
        if ($limitInstitution > 0) {
            $org = DB::fetch('SELECT * FROM institutions WHERE id = ?', [$limitInstitution]);
            $orgs = $org ? [$org] : [];
        } else {
            $orgs = DB::fetchAll('SELECT * FROM institutions ORDER BY id');
        }

        $tree = [];
        foreach ($orgs as $org) {
            if (!$org) {
                continue;
            }
            $leaders = DB::fetchAll('SELECT * FROM leaders WHERE institution_id = ? ORDER BY id', [$org['id']]);
            $leaderNodes = [];
            foreach ($leaders as $leader) {
                $salesCount = DB::count('sales', ['leader_id' => $leader['id']]);
                $leaderNodes[] = [
                    'id' => 'leader_' . $leader['id'],
                    'name' => $leader['name'],
                    'type' => 'leader',
                    'sales_count' => $salesCount,
                ];
            }
            $tree[] = [
                'id' => 'org_' . $org['id'],
                'name' => $org['short_name'] ?: $org['full_name'],
                'type' => 'institution',
                'children' => $leaderNodes,
            ];
        }
        return $tree;
    }
}
