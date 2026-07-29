<template>
  <div>
    <el-card>
      <el-form label-width="120px" style="max-width: 520px">
        <el-form-item label="分享域名">
          <el-input v-model="domain" placeholder="如 http://localhost:8000" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="save">保存</el-button>
          <span class="tip">该域名用于生成团队长邀请二维码链接</span>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getSettings, updateDomain } from '../../api/settings'
import { ElMessage } from 'element-plus'

const domain = ref('')
const loading = ref(false)

onMounted(async () => {
  try {
    domain.value = (await getSettings()).share_domain || ''
  } catch (e) {
    // 拦截器已提示
  }
})

async function save() {
  if (!domain.value) {
    ElMessage.warning('请输入分享域名')
    return
  }
  loading.value = true
  try {
    await updateDomain(domain.value)
    ElMessage.success('已保存')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.tip {
  margin-left: 12px;
  color: #999;
  font-size: 12px;
}
</style>
