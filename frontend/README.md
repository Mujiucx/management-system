# 渠道代理管理系统 — 前端 (frontend)

Vue3 + Vite5 + vue-router4 + pinia2 + axios + Element Plus + qrcode。

## 依赖与启动

```bash
npm install        # 安装依赖（需网络）
npm run dev        # 开发服务器，默认 http://localhost:5173
npm run build      # 生产构建，产物在 dist/
npm run preview    # 预览构建产物
```

## 代理

`vite.config.js` 已配置 `/api` 代理到后端 `http://localhost:8000`，
因此开发态前端直接访问 `/api/xxx` 即可，无需手动跨域处理。

## 目录结构

```
frontend/
├── index.html
├── vite.config.js
├── package.json
└── src/
    ├── main.js              # 入口，挂载 app / router / pinia / ElementPlus
    ├── App.vue
    ├── router/index.js      # 路由 + 登录守卫 + 角色菜单可见性
    ├── store/auth.js        # pinia 登录态
    ├── styles/theme.css     # 蓝灰主题 token
    ├── api/                # request 拦截器 + 各模块 REST 封装
    ├── layout/AdminLayout.vue
    ├── components/          # DataTable / BaseDialog / StatusTag / QrCode
    └── views/
        ├── Login.vue
        ├── Overview.vue
        ├── org/             # OrgList / OrgFormDialog / OrgDetailDialog
        ├── leader/          # LeaderList / LeaderFormDialog / LeaderDetailDialog
        ├── sales/           # SalesList / SalesDetailDialog（只读+启停）
        └── settings/Settings.vue
```

## 说明
- 机构管理员（institution）前端不显示「机构管理」与「系统设置」菜单（路由守卫 + 菜单 v-if 双重拦截）。
- 业务员 PC 端只读，仅支持启停（无新建/删除按钮）。
- 登录方式：密码登录 / 验证码登录（开发态 GET 验证码接口会回传明文 code）。
- 团队长创建后，后端自动按 `share_domain` 生成邀请二维码链接 `qr_link`，详情页用 qrcode 渲染。
