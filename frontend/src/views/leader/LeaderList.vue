<template>
  <div>
    <el-card>
      <el-form :inline="true" :model="filters">
        <el-form-item label="关键词">
          <el-input
            v-model="filters.keyword"
            placeholder="姓名/手机号/邀请码"
            clearable
            @keyup.enter="load"
          />
        </el-form-item>
        <el-form-item label="机构" v-if="auth.isPlatform">
          <el-select
            v-model="filters.institution_id"
            placeholder="全部"
            clearable
            filterable
            style="width: 160px"
          >
            <el-option v-for="o in orgs" :key="o.id" :label="o.short_name" :value="o.id" />
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
          <el-button type="success" @click="openCreate">新建团队长</el-button>
        </el-form-item>
      </el-form>

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
          <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
          <el-switch
            :model-value="row.status === 'active'"
            @change="(v) => toggleStatus(row, v)"
            style="margin: 0 8px"
          />
          <el-popconfirm title="确认删除该团队长？" @confirm="remove(row)">
            <template #reference>
              <el-button link type="danger">删除</el-button>
            </template>
          </el-popconfirm>
        </template>
      </DataTable>
    </el-card>

    <LeaderFormDialog v-model="formVisible" :edit-data="editData" @saved="load" />
    <LeaderDetailDialog v-model="detailVisible" :id="detailId" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { listLeaders, deleteLeader, setLeaderStatus } from '../../api/leader'
import { listOrgs } from '../../api/org'
import { useAuthStore } from '../../store/auth'
import { ElMessage } from 'element-plus'
import DataTable from '../../components/DataTable.vue'
import StatusTag from '../../components/StatusTag.vue'
import LeaderFormDialog from './LeaderFormDialog.vue'
import LeaderDetailDialog from './LeaderDetailDialog.vue'

const auth = useAuthStore()

const columns = [
  { prop: 'name', label: '团队长', minWidth: 110 },
  { prop: 'institution_name', label: '所属机构', minWidth: 130 },
  { prop: 'phone', label: '登录手机号', minWidth: 130 },
  { prop: 'leader_code', label: '邀请码', minWidth: 100 },
  { prop: 'status', label: '状态', width: 90, slot: 'status' },
  { prop: 'created_at', label: '创建时间', minWidth: 160 },
  { prop: '__actions', label: '操作', width: 240, slot: 'actions' },
]

const list = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const filters = reactive({ keyword: '', status: '', institution_id: '' })
const orgs = ref([])

const formVisible = ref(false)
const editData = ref(null)
const detailVisible = ref(false)
const detailId = ref(null)

async function load() {
  const res = await listLeaders({ ...filters, page: page.value, page_size: pageSize.value })
  list.value = res.list
  total.value = res.total
}

function onPageChange({ page: p, pageSize: s }) {
  page.value = p
  pageSize.value = s
  load()
}

function openCreate() {
  editData.value = null
  formVisible.value = true
}
function openEdit(row) {
  editData.value = row
  formVisible.value = true
}
function openDetail(row) {
  detailId.value = row.id
  detailVisible.value = true
}
async function toggleStatus(row, active) {
  await setLeaderStatus(row.id, active ? 'active' : 'disabled')
  ElMessage.success('操作成功')
  load()
}
async function remove(row) {
  await deleteLeader(row.id)
  ElMessage.success('已删除')
  load()
}

onMounted(async () => {
  if (auth.isPlatform) {
    orgs.value = (await listOrgs({ page: 1, page_size: 100 })).list
  }
  load()
})
</script>
