<?php
/**
 * services/SalesService.php
 * 业务员（只读 + 启停）查询封装。
 */

namespace Services;

use Utils\DB;

class SalesService
{
    /**
     * 列表，按 scope 过滤；institution 模式按下属 leader_ids 过滤。
     */
    public static function list(array $filters, array $scope, int $page, int $pageSize): array
    {
        $cond = [];
        $args = [];

        if ($scope['type'] === 'institution') {
            $leaderIds = $scope['leader_ids'] ?? [];
            if (empty($leaderIds)) {
                return ['list' => [], 'total' => 0];
            }
            $ph = implode(',', array_fill(0, count($leaderIds), '?'));
            $cond[] = "leader_id IN ($ph)";
            $args = array_merge($args, $leaderIds);
        }

        $keyword = trim($filters['keyword'] ?? '');
        $status = trim($filters['status'] ?? '');
        $leaderId = trim($filters['leader_id'] ?? '');

        if ($keyword !== '') {
            $cond[] = '(name LIKE ? OR phone LIKE ?)';
            $args = array_merge($args, ["%$keyword%", "%$keyword%"]);
        }
        if ($status !== '') {
            $cond[] = 'status = ?';
            $args[] = $status;
        }
        if ($leaderId !== '' && $scope['type'] === 'platform') {
            $cond[] = 'leader_id = ?';
            $args[] = (int) $leaderId;
        }

        $where = $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
        $total = (int) DB::fetch("SELECT COUNT(*) AS c FROM sales $where", $args)['c'];
        $offset = ($page - 1) * $pageSize;
        $list = DB::fetchAll("SELECT * FROM sales $where ORDER BY id DESC LIMIT $offset, $pageSize", $args);

        foreach ($list as &$s) {
            $l = DB::fetch('SELECT name FROM leaders WHERE id = ?', [$s['leader_id']]);
            $s['leader_name'] = $l ? $l['name'] : '';
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 详情，附带上级团队长与机构名。
     */
    public static function detail(int $id): ?array
    {
        $sales = DB::fetch('SELECT * FROM sales WHERE id = ?', [$id]);
        if (!$sales) {
            return null;
        }
        $leader = DB::fetch('SELECT name, institution_id FROM leaders WHERE id = ?', [$sales['leader_id']]);
        $sales['leader_name'] = $leader ? $leader['name'] : '';
        if ($leader) {
            $inst = DB::fetch('SELECT short_name, full_name FROM institutions WHERE id = ?', [$leader['institution_id']]);
            $sales['institution_name'] = $inst ? ($inst['short_name'] ?: $inst['full_name']) : '';
        }
        return $sales;
    }
}
