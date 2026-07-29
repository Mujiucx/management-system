<?php
/**
 * services/LeaderService.php
 * 团队长相关查询与创建逻辑封装。
 */

namespace Services;

use Utils\DB;
use Utils\Auth;

class LeaderService
{
    /**
     * 列表，支持 keyword/status/institution_id 过滤；institution 模式按 scope 限定本机构。
     */
    public static function list(array $filters, array $scope, int $page, int $pageSize): array
    {
        $cond = [];
        $args = [];

        if ($scope['type'] === 'institution') {
            $cond[] = 'institution_id = ?';
            $args[] = $scope['institution_id'];
        }
        $keyword = trim($filters['keyword'] ?? '');
        $status = trim($filters['status'] ?? '');
        $institutionId = trim($filters['institution_id'] ?? '');

        if ($keyword !== '') {
            $cond[] = '(name LIKE ? OR nickname LIKE ? OR phone LIKE ? OR leader_code LIKE ?)';
            $args = array_merge($args, ["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"]);
        }
        if ($status !== '') {
            $cond[] = 'status = ?';
            $args[] = $status;
        }
        if ($institutionId !== '' && $scope['type'] === 'platform') {
            $cond[] = 'institution_id = ?';
            $args[] = (int) $institutionId;
        }

        $where = $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
        $total = (int) DB::fetch("SELECT COUNT(*) AS c FROM leaders $where", $args)['c'];
        $offset = ($page - 1) * $pageSize;
        $list = DB::fetchAll("SELECT * FROM leaders $where ORDER BY id DESC LIMIT $offset, $pageSize", $args);

        foreach ($list as &$l) {
            $inst = DB::fetch('SELECT short_name, full_name FROM institutions WHERE id = ?', [$l['institution_id']]);
            $l['institution_name'] = $inst ? ($inst['short_name'] ?: $inst['full_name']) : '';
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 创建团队长 + 自动建登录账户 + 生成 qr_link。
     */
    public static function create(array $data, string $password): array
    {
        $leaderCode = trim($data['leader_code'] ?? '');
        if ($leaderCode === '') {
            $seq = (int) DB::fetch('SELECT COUNT(*) AS c FROM leaders')['c'] + 1;
            $leaderCode = 'L' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }

        $leaderId = DB::insert('leaders', [
            'name' => $data['name'],
            'nickname' => ($data['nickname'] ?? '') ?: null,
            'phone' => $data['phone'],
            'institution_id' => (int) $data['institution_id'],
            'status' => 'active',
            'leader_code' => $leaderCode,
            'qr_link' => '',
        ]);

        $shareDomain = SettingService::getShareDomain();
        $qrLink = rtrim($shareDomain, '/') . '/invite?id=' . $leaderId;
        DB::update('leaders', ['qr_link' => $qrLink], ['id' => $leaderId]);

        DB::insert('accounts', [
            'role' => 'leader',
            'phone' => $data['phone'],
            'password' => Auth::hashPassword($password),
            'status' => 'active',
            'institution_id' => (int) $data['institution_id'],
            'leader_id' => $leaderId,
            'ref_type' => 'leader',
            'ref_id' => $leaderId,
        ]);

        return DB::fetch('SELECT * FROM leaders WHERE id = ?', [$leaderId]);
    }

    /**
     * 详情，附带下属业务员列表与数量。
     */
    public static function detail(int $id): ?array
    {
        $leader = DB::fetch('SELECT * FROM leaders WHERE id = ?', [$id]);
        if (!$leader) {
            return null;
        }
        $inst = DB::fetch('SELECT short_name, full_name FROM institutions WHERE id = ?', [$leader['institution_id']]);
        $leader['institution_name'] = $inst ? ($inst['short_name'] ?: $inst['full_name']) : '';
        $sales = DB::fetchAll(
            'SELECT id, name, phone, bound_customers, monthly_performance, status FROM sales WHERE leader_id = ? ORDER BY id DESC',
            [$id]
        );
        $leader['sales_list'] = $sales;
        $leader['sales_count'] = count($sales);
        return $leader;
    }
}
