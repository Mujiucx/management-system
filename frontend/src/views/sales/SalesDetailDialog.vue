<template>
  <BaseDialog v-model="visible" title="业务员详情" width="520px">
    <div v-loading="loading">
      <el-descriptions :column="1" border v-if="detail">
        <el-descriptions-item label="姓名">{{ detail.name }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ detail.phone }}</el-descriptions-item>
        <el-descriptions-item label="上级团队长">{{ detail.leader_name }}</el-descriptions-item>
        <el-descriptions-item label="所属机构">{{ detail.institution_name || '—' }}</el-descriptions-item>
        <el-descriptions-item label="绑定客户数">{{ detail.bound_customers }}</el-descriptions-item>
        <el-descriptions-item label="本月业绩">{{ detail.monthly_performance }}</el-descriptions-item>
        <el-descriptions-item label="状态"><StatusTag :value="detail.status" /></el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ detail.created_at }}</el-descriptions-item>
      </el-descriptions>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseDialog from '../../components/BaseDialog.vue'
import StatusTag from '../../components/StatusTag.vue'
import { getSales } from '../../api/sales'

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
        detail.value = await getSales(props.id)
      } finally {
        loading.value = false
      }
    }
  },
)
</script>
