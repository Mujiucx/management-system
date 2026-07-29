<template>
  <div>
    <el-row :gutter="16">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-title">机构总数</div>
          <div class="stat-num">{{ stats.org_count }}</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-title">团队长总数</div>
          <div class="stat-num">{{ stats.leader_count }}</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-title">业务员总数</div>
          <div class="stat-num">{{ stats.sales_count }}</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-title">
            本月业绩 <small class="hint">（来自 H5）</small>
          </div>
          <div class="stat-num">{{ stats.monthly_performance }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-card class="tree-card">
      <template #header>组织层级</template>
      <el-tree :data="treeData" :props="treeProps" default-expand-all>
        <template #default="{ data }">
          <span class="node">
            <el-icon v-if="data.type === 'institution'"><OfficeBuilding /></el-icon>
            <el-icon v-else><UserFilled /></el-icon>
            <span class="node-name">{{ data.name }}</span>
            <small v-if="data.sales_count != null" class="node-sub">
              （业务员 {{ data.sales_count }}）
            </small>
          </span>
        </template>
      </el-tree>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getOverview } from '../api/overview'

const stats = ref({
  org_count: 0,
  leader_count: 0,
  sales_count: 0,
  monthly_performance: 0,
})
const treeData = ref([])
const treeProps = { children: 'children', label: 'name' }

onMounted(async () => {
  try {
    const data = await getOverview()
    stats.value = data
    treeData.value = data.tree || []
  } catch (e) {
    // 拦截器已提示
  }
})
</script>

<style scoped>
.stat-title {
  color: #667;
  font-size: 13px;
  margin-bottom: 10px;
}
.stat-title .hint {
  color: #aaa;
  font-weight: 400;
}
.stat-num {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-primary);
}
.tree-card {
  margin-top: 16px;
}
.node {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.node-name {
  font-weight: 600;
}
.node-sub {
  color: #999;
}
</style>
