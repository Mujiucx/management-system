<template>
  <BaseDialog v-model="visible" title="机构详情" width="560px">
    <div v-loading="loading">
      <el-descriptions :column="1" border v-if="detail">
        <el-descriptions-item label="企业全称">{{ detail.full_name }}</el-descriptions-item>
        <el-descriptions-item label="企业简称">{{ detail.short_name }}</el-descriptions-item>
        <el-descriptions-item label="统一社会信用代码">{{ detail.license_no }}</el-descriptions-item>
        <el-descriptions-item label="营业执照">
          <a v-if="detail.license_path" :href="detail.license_path" target="_blank">查看文件</a>
          <span v-else>无</span>
        </el-descriptions-item>
        <el-descriptions-item label="联系人">{{ detail.contact_name || '无' }}</el-descriptions-item>
        <el-descriptions-item label="联系人手机号">{{ detail.contact_phone }}</el-descriptions-item>
        <el-descriptions-item label="状态"><StatusTag :value="detail.status" /></el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ detail.created_at }}</el-descriptions-item>
        <el-descriptions-item label="下属团队长数">{{ detail.leader_count }}</el-descriptions-item>
        <el-descriptions-item label="下属业务员数">{{ detail.sales_count }}</el-descriptions-item>
      </el-descriptions>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseDialog from '../../components/BaseDialog.vue'
import StatusTag from '../../components/StatusTag.vue'
import { getOrg } from '../../api/org'

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
        detail.value = await getOrg(props.id)
      } finally {
        loading.value = false
      }
    }
  },
)
</script>
