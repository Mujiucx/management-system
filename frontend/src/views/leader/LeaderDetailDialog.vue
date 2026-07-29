<template>
  <BaseDialog v-model="visible" title="团队长详情" width="600px">
    <div v-loading="loading">
      <el-descriptions :column="1" border v-if="detail">
        <el-descriptions-item label="姓名">{{ detail.name }}</el-descriptions-item>
        <el-descriptions-item label="昵称">{{ detail.nickname || '无' }}</el-descriptions-item>
        <el-descriptions-item label="所属机构">{{ detail.institution_name }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ detail.phone }}</el-descriptions-item>
        <el-descriptions-item label="邀请码">{{ detail.leader_code }}</el-descriptions-item>
        <el-descriptions-item label="状态"><StatusTag :value="detail.status" /></el-descriptions-item>
        <el-descriptions-item label="邀请二维码">
          <QrCode v-if="detail.qr_link" :value="detail.qr_link" />
          <span v-else>无</span>
        </el-descriptions-item>
        <el-descriptions-item label="下属业务员数">{{ detail.sales_count }}</el-descriptions-item>
      </el-descriptions>

      <el-divider>下属业务员</el-divider>
      <el-empty v-if="!detail.sales_list || detail.sales_list.length === 0" description="暂无业务员" />
      <el-row :gutter="12" v-else>
        <el-col :span="8" v-for="s in detail.sales_list" :key="s.id">
          <el-card shadow="hover" class="sales-card">
            <div class="sales-name">{{ s.name }}</div>
            <div class="sales-meta">手机：{{ s.phone }}</div>
            <div class="sales-meta">绑定客户：{{ s.bound_customers }}</div>
            <div class="sales-meta">本月业绩：{{ s.monthly_performance }}</div>
            <div><StatusTag :value="s.status" /></div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseDialog from '../../components/BaseDialog.vue'
import StatusTag from '../../components/StatusTag.vue'
import QrCode from '../../components/QrCode.vue'
import { getLeader } from '../../api/leader'

const props = defineProps({
  modelValue: Boolean,
  id: { type: [Number, String], default: null },
})
const emit = defineEmits(['update:modelValue'])
const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
const detail = ref(null)
const loading = ref(false)

watch(
  () => props.modelValue,
  async (v) => {
    if (v && props.id) {
      loading.value = true
      try {
        detail.value = await getLeader(props.id)
      } finally {
        loading.value = false
      }
    }
  },
)
</script>

<style scoped>
.sales-card {
  margin-bottom: 12px;
}
.sales-name {
  font-weight: 600;
  margin-bottom: 6px;
}
.sales-meta {
  font-size: 12px;
  color: #666;
  line-height: 1.6;
}
</style>
