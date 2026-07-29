# 渠道代理管理系统 — 后端 (backend)

纯 PHP（>=8.1）实现，PDO + MySQL，**不依赖 Composer / 任何第三方库**。

## 目录结构

```
backend/
├── public/
│   ├── index.php        # 单一入口（路由分发）
│   ├── .htaccess       # Apache 重写到 index.php
│   └── uploads/        # 上传文件目录（.gitkeep 占位）
├── config/
│   ├── config.php      # 全局常量（DB / DEV_MODE / 上传限制 / CORS）
│   └── db.php         # PDO 单例 getPDO()
├── utils/
│   ├── db.php         # DB 助手（query/fetch/fetchAll/insert/update/delete/count）
│   ├── response.php   # 统一 JSON 响应信封
│   ├── auth.php       # 密码哈希 / token / 会话
│   ├── upload.php     # 文件上传
│   ├── sms.php        # 模拟短信验证码
│   └── Context.php    # 请求上下文（account / scope）
├── router/Router.php  # 轻量路由（:param 占位）
├── middleware/        # AuthMiddleware / ScopeMiddleware
├── controllers/       # Auth / Org / Leader / Sales / Settings / Overview
├── services/          # Setting / Institution / Leader / Sales 业务封装
└── db/
    ├── schema.sql     # 建表 DDL
    └── seed.php       # CLI 演示数据（php db/seed.php）
```

## 运行步骤

### 1. 准备数据库（MySQL >= 5.7）
```sql
CREATE DATABASE agency_admin DEFAULT CHARSET utf8mb4;
```

### 2. 修改配置
编辑 `config/config.php`，按需调整：
- `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS`
- `DEV_MODE`：开发态 `true`（短信验证码在响应里明文返回）；生产态改 `false`
- `DEFAULT_SHARE_DOMAIN`：生成邀请二维码前缀

### 3. 建表 + 灌演示数据
```bash
php db/seed.php
```
将创建所有表，并插入：平台管理员(13800000000)、机构示例科技(13800000001)、团队长李团队(13800000002)、业务员王业务(13800000003)。**默认密码均为 `admin123`**。

### 4. 启动后端（PHP 内置服务器）
```bash
cd backend/public
php -S 0.0.0.0:8000
```
入口为 `http://localhost:8000/api/...`；前端 dev server 通过 Vite proxy 转发 `/api`。

### 5. 前端
见 `../frontend/README.md`（或运行说明）。开发态访问 `http://localhost:5173`。

## 接口约定
- 统一响应：`{"code":0,"msg":"success","data":...}`；失败 `{"code":<非零>,"msg":"..."}`。
- 错误码：401 未登录/令牌失效；403 无权限(含越权)；400 参数错误；404 不存在；409 冲突；500 服务器错误。
- 列表：`{"list":[...],"total":N,"page":1,"page_size":20}`；入参 `page`(默认1)/`page_size`(默认20,上限100)。
- 鉴权：`Authorization: Bearer <token>`，token 有效期 7 天。
- 数据权限：platform=全部；institution=本机构子树（leaders 按 institution_id，sales 按 leader_id IN 本机构团队长）。

## 说明
- 短信验证码为模拟实现，开发态（`DEV_MODE=true`）`POST /api/auth/send-code` 在 `data.code` 返回明文 6 位码。
- 营业执照上传：`POST /api/upload`（multipart，`file` + `module`），保存至 `public/uploads/<module>/`，返回 `{url:'/uploads/...'}`。
