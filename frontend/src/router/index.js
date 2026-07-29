import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

import Login from '../views/Login.vue'
import AdminLayout from '../layout/AdminLayout.vue'
import Overview from '../views/Overview.vue'
import OrgList from '../views/org/OrgList.vue'
import LeaderList from '../views/leader/LeaderList.vue'
import SalesList from '../views/sales/SalesList.vue'
import Settings from '../views/settings/Settings.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { public: true },
  },
  {
    path: '/',
    component: AdminLayout,
    redirect: '/overview',
    children: [
      { path: 'overview', name: 'overview', component: Overview },
      { path: 'orgs', name: 'orgs', component: OrgList, meta: { platformOnly: true } },
      { path: 'leaders', name: 'leaders', component: LeaderList },
      { path: 'sales', name: 'sales', component: SalesList },
      { path: 'settings', name: 'settings', component: Settings, meta: { platformOnly: true } },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/overview' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  const loggedIn = auth.isLoggedIn

  // 已登录访问登录页 -> 概览
  if (to.meta.public) {
    if (loggedIn) return { name: 'overview' }
    return true
  }

  // 未登录 -> 登录
  if (!loggedIn) return { name: 'login' }

  // 平台专属菜单，机构/其他角色不可见
  if (to.meta.platformOnly && !auth.isPlatform) {
    return { name: 'overview' }
  }

  return true
})

export default router
