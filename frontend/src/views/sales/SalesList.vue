<template>
  <div>
    <el-card>
      <el-form :inline="true" :model="filters">
        <el-form-item label="关键词">
          <el-input
            v-model="filters.keyword"
            placeholder="姓名/手机号"
            clearable
            @keyup.enter="load"
          />
        </el-form-item>
        <el-form-item label="团队长" v-if="auth.isPlatform">
          <el-select
            v-model="filters.leader_id"
            placeholder="全部"
            clearable
            filterable
            style="width: 160px"
          >
            <el-option v-for="l in leaders" :key="l.id" :label="l.name" :value="l.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="filters.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="启用" value="active" />
            <el-option label="禁用" value="disabled" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="load">查询</el-button>
        </el-form-item>
      </el-form>

      <el-alert
        type="info"
        :closable="false"
        title="业务员为只读（数据来自 H5 端），仅可启停，不可在 PC 端创建/删除。"
        style="margin-bottom: 12px"
      />

      <DataTable
        :columns="columns"
        :data="list"
        :total="total"
        :page="page"
        :page-size="pageSize"
        @page-change="onPageChange"
      >
        <template #status="{ row }"><StatusTag :value="row.status" /></template>
        <template #actions="{ row }">
          <el-button link type="primary" @click="openDetail(row)">详情</el-button>
          <el-switch
            :model-value="row.status === 'active'"
            @change="(v) => toggleStatus(row, v)"
          />
        </template>
      </DataTable>
    </el-card>

    <SalesDetailDialog v-model="detailVisible" :id="detailId" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { listSales, setSalesStatus } from '../../api/sales'
import { listLeaders } from '../../api/leader'
import { useAuthStore } from '../../store/auth'
import { ElMessage } from 'element-plus'
import DataTable from '../../components/DataTable.vue'
import StatusTag from '../../components/StatusTag.vue'
import SalesDetailDialog from './SalesDetailDialog.vue'

const auth = useAuthStore()

const columns = [
  { prop: 'name', label: '业务员', minWidth: 110 },
  { prop: 'leader_name', label: '上级团队长', minWidth: 120 },
  { prop: 'phone', label: '登录手机号', minWidth: 130 },
  { prop: 'bound_customers', label: '绑定客户', width: 100 },
  { prop: 'monthly_performance', label: '本月业绩', width: 120 },
  { prop: 'status', label: '状态', width: 90, slot: 'status' },
  { prop: 'created_at', label: '创建时间', minWidth: 150 },
  { prop: '__actions', label: '操作', width: 160, slot: 'actions' },
]

const list = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const filters = reactive({ keyword: '', status: '', leader_id: '' })
const leaders = ref([])

const detailVisible = ref(false)
const detailId = ref(null)

async function load() {
  const res = await listSales({ ...filters, page: page.value, page_size: pageSize.value })
  list.value = res.list
  total.value = res.total
}

function onPageChange({ page: p, pageSize: s }) {
  page.value = p
  pageSize.value = s
  load()
}

function openDetail(row) {
  detailId.value = row.id
  detailVisible.value = true
}

async function toggleStatus(row, active) {
  await setSalesStatus(row.id, active ? 'active' : 'disabled')
  ElMessage.success('操作成功')
  load()
}

onMounted(async () => {
  if (auth.isPlatform) {
    leaders.value = (await listLeaders({ page: 1, page_size: 100 })).list
  }
  load()
})
</script>
