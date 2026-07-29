<?php
/**
 * utils/Context.php
 * 请求上下文：在中间件中注入当前账户与数据权限 scope，供 controller 读取。
 */

namespace Utils;

class Context
{
    /** @var array|null 当前登录账户 */
    public static ?array $auth = null;

    /** @var array|null 数据权限 scope */
    public static ?array $scope = null;
}
