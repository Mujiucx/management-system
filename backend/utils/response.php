<?php
/**
 * utils/response.php
 * 统一 JSON 响应信封：
 *   成功 -> {"code":0,"msg":"success","data":...}
 *   失败 -> {"code":<非零>,"msg":"...","data":null}
 *   列表 -> {"code":0,"msg":"success","data":{"list":[...],"total":N,"page":1,"page_size":20}}
 */

namespace Utils;

class Response
{
    /**
     * 输出 JSON 并终止脚本。
     */
    public static function json($data, int $httpStatus = 200): void
    {
        if (defined('CORS') && CORS) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
        header('Content-Type: application/json; charset=utf-8');
        if ($httpStatus !== 200) {
            http_response_code($httpStatus);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * 成功响应（带 data）。
     */
    public static function ok($data = null): void
    {
        self::json(['code' => 0, 'msg' => 'success', 'data' => $data]);
    }

    /**
     * 失败响应。默认 HTTP 200，错误码通过 body.code 表达。
     */
    public static function error(int $code, string $msg, int $httpStatus = 200): void
    {
        self::json(['code' => $code, 'msg' => $msg, 'data' => null], $httpStatus);
    }

    /**
     * 列表分页响应。
     */
    public static function listResp(array $list, int $total, int $page, int $pageSize): void
    {
        self::json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ],
        ]);
    }
}
