<?php
/**
 * middleware/ScopeMiddleware.php
 * 依据 account.role 计算数据权限 scope 并注入 Context：
 *   platform   -> 无限制（全部）
 *   institution-> 本机构子树：institution_id + 下属 leader_ids
 *   leader     -> 自身（leader_id）
 *   sales      -> 自身（sales_id）
 */

namespace Middleware;

use Utils\Auth;
use Utils\Context;
use Utils\DB;
use Utils\Response;

class ScopeMiddleware
{
    public static function handle(): void
    {
        $account = Context::$auth;
        if (!$account) {
            Response::error(401, '未授权', 401);
        }

        $role = $account['role'];
        $scope = ['role' => $role];

        switch ($role) {
            case 'platform':
                $scope['type'] = 'platform';
                break;

            case 'institution':
                $institutionId = (int) $account['institution_id'];
                $leaders = DB::fetchAll(
                    'SELECT id FROM leaders WHERE institution_id = ?',
                    [$institutionId]
                );
                $scope['type'] = 'institution';
                $scope['institution_id'] = $institutionId;
                $scope['leader_ids'] = array_map('intval', array_column($leaders, 'id'));
                break;

            case 'leader':
                $scope['type'] = 'leader';
                $scope['leader_id'] = (int) $account['leader_id'];
                break;

            case 'sales':
                $scope['type'] = 'sales';
                $scope['sales_id'] = (int) ($account['ref_id'] ?? 0);
                break;

            default:
                $scope['type'] = $role;
                break;
        }

        Context::$scope = $scope;
    }
}
