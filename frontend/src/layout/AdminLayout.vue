<template>
  <el-container class="layout">
    <el-aside width="220px" class="sidebar">
      <div class="logo">渠道代理管理</div>
      <el-menu
        :default-active="activeMenu"
        class="menu"
        background-color="transparent"
        text-color="#cdd7e6"
        active-text-color="#ffffff"
        router
      >
        <el-menu-item index="/overview">
          <el-icon><DataLine /></el-icon><span>概览</span>
        </el-menu-item>
        <el-menu-item v-if="auth.isPlatform" index="/orgs">
          <el-icon><OfficeBuilding /></el-icon><span>机构管理</span>
        </el-menu-item>
        <el-menu-item index="/leaders">
          <el-icon><UserFilled /></el-icon><span>团队长管理</span>
        </el-menu-item>
        <el-menu-item index="/sales">
          <el-icon><User /></el-icon><span>业务员管理</span>
        </el-menu-item>
        <el-menu-item v-if="auth.isPlatform" index="/settings">
          <el-icon><Setting /></el-icon><span>系统设置</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header class="header">
        <div class="breadcrumb">{{ currentTitle }}</div>
        <div class="right">
          <span class="role-text">当前角色：{{ roleText }}</span>
          <el-button text type="primary" @click="handleLogout">退出登录</el-button>
        </div>
      </el-header>

      <el-main class="main">
        <div class="scope-banner">
          数据范围：{{ auth.isPlatform ? '全部数据' : '本机构及下级' }}
        </div>
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { ElMessage, ElMessageBox } from 'element-plus'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const menuTitles = {
  overview: '概览',
  orgs: '机构管理',
  leaders: '团队长管理',
  sales: '业务员管理',
  settings: '系统设置',
}
const currentTitle = computed(() => menuTitles[route.name] || '概览')
const activeMenu = computed(() => '/' + (route.path.split('/')[1] || 'overview'))

const roleText = computed(() => {
  const map = {
    platform: '平台管理员',
    institution: '机构管理员',
    leader: '团队长',
    sales: '业务员',
  }
  return map[auth.role] || auth.role
})

async function handleLogout() {
  try {
    await ElMessageBox.confirm('确认退出登录？', '提示', { type: 'warning' })
  } catch (e) {
    return
  }
  await auth.logout()
  ElMessage.success('已退出')
  router.push({ name: 'login' })
}
</script>

<style scoped>
.layout {
  height: 100vh;
}
.sidebar {
  background: var(--sidebar-gradient);
  color: #fff;
  overflow: hidden;
}
.logo {
  height: 60px;
  line-height: 60px;
  text-align: center;
  font-size: 18px;
  font-weight: 600;
  color: #fff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.menu {
  border-right: none;
}
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}
.breadcrumb {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-primary);
}
.right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.role-text {
  color: #667;
  font-size: 14px;
}
.main {
  background: var(--color-bg);
}
.scope-banner {
  background: #eef3fa;
  color: var(--color-primary);
  padding: 8px 14px;
  border-radius: 6px;
  margin-bottom: 16px;
  font-size: 13px;
}
</style>
