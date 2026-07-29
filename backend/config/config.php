<?php
/**
 * config/config.php
 * 全局配置常量。无第三方依赖，纯 PHP（>=8.1）。
 * 可被 index.php（运行时）与 db/seed.php（CLI）复用。
 */

// 数据库
defined('DB_HOST') or define('DB_HOST', '127.0.0.1');
defined('DB_NAME') or define('DB_NAME', 'agency_admin');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

// 运行模式
defined('DEV_MODE') or define('DEV_MODE', true); // 开发态：短信验证码在响应中明文返回

// 令牌与鉴权
defined('TOKEN_TTL_DAYS') or define('TOKEN_TTL_DAYS', 7); // Bearer token 有效期

// 系统设置兜底
defined('DEFAULT_SHARE_DOMAIN') or define('DEFAULT_SHARE_DOMAIN', 'http://localhost:8000');

// 文件上传
defined('UPLOAD_MAX_SIZE') or define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
defined('ALLOWED_EXT') or define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'pdf']);

// CORS
defined('CORS') or define('CORS', true);
