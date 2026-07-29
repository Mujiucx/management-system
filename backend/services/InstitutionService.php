<?php
/**
 * services/InstitutionService.php
 * 机构相关查询封装。
 */

namespace Services;

use Utils\DB;

class InstitutionService
{
    /**
     * 机构列表（含下属团队长/业务员数量下钻）。
     */
    public static function list(array $filters, int $page, int $pageSize): array
    {
        $cond = [];
        $args = [];
        $keyword = trim($filters['keyword'] ?? '');
        $status = trim($filters['status'] ?? '');

        if ($keyword !== '') {
            $cond[] = '(full_name LIKE ? OR short_name LIKE ? OR contact_phone LIKE ? OR license_no LIKE ?)';
            $args = array_merge($args, ["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"]);
        }
        if ($status !== '') {
            $cond[] = 'status = ?';
            $args[] = $status;
        }
        $where = $cond ? 'WHERE ' . implode(' AND ', $cond) : '';

        $total = (int) DB::fetch("SELECT COUNT(*) AS c FROM institutions $where", $args)['c'];
        $offset = ($page - 1) * $pageSize;
        $list = DB::fetchAll("SELECT * FROM institutions $where ORDER BY id DESC LIMIT $offset, $pageSize", $args);

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 机构详情，附带 leader/sales 数量。
     */
    public static function detail(int $id): ?array
    {
        $org = DB::fetch('SELECT * FROM institutions WHERE id = ?', [$id]);
        if (!$org) {
            return null;
        }
        $leaderCount = DB::count('leaders', ['institution_id' => $id]);
        $leaderIds = array_column(DB::fetchAll('SELECT id FROM leaders WHERE institution_id = ?', [$id]), 'id');
        $salesCount = 0;
        if ($leaderIds) {
            $ph = implode(',', array_fill(0, count($leaderIds), '?'));
            $salesCount = (int) DB::fetch("SELECT COUNT(*) AS c FROM sales WHERE leader_id IN ($ph)", $leaderIds)['c'];
        }
        $org['leader_count'] = $leaderCount;
        $org['sales_count'] = $salesCount;
        return $org;
    }

    /**
     * 机构可选项（供团队长表单下拉）。
     */
    public static function options(): array
    {
        return DB::fetchAll('SELECT id, short_name, full_name FROM institutions ORDER BY id');
    }
}
