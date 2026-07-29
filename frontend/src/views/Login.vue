<template>
  <div class="login-page">
    <div class="brand">
      <div class="brand-title">渠道代理管理系统</div>
      <div class="brand-sub">AGENCY ADMIN PLATFORM</div>
      <div class="chips">
        <el-tag class="chip">总后台</el-tag>
        <span class="arrow">→</span>
        <el-tag class="chip">机构</el-tag>
        <span class="arrow">→</span>
        <el-tag class="chip">团队长</el-tag>
        <span class="arrow">→</span>
        <el-tag class="chip">业务员</el-tag>
      </div>
      <div class="brand-tip">分级管理 · 邀请裂变 · 数据看板</div>
    </div>

    <div class="form-panel">
      <el-card class="login-card">
        <el-tabs v-model="tab">
          <el-tab-pane label="密码登录" name="password" />
          <el-tab-pane label="验证码登录" name="sms" />
        </el-tabs>

        <el-form :model="form" :rules="rules" ref="formRef" label-width="0">
          <el-form-item prop="phone">
            <el-input v-model="form.phone" placeholder="请输入手机号" :prefix-icon="Iphone">
              <template #prepend>+86</template>
            </el-input>
          </el-form-item>

          <el-form-item v-if="tab === 'password'" prop="password">
            <el-input
              v-model="form.password"
              type="password"
              placeholder="请输入密码"
              show-password
              :prefix-icon="Lock"
            />
          </el-form-item>

          <el-form-item v-else prop="code">
            <el-input v-model="form.code" placeholder="请输入验证码">
              <template #append>
                <el-button :disabled="countdown > 0" @click="sendCode">
                  {{ countdown > 0 ? countdown + 's 后重发' : '获取验证码' }}
                </el-button>
              </template>
            </el-input>
          </el-form-item>

          <el-button type="primary" class="submit" :loading="loading" @click="onSubmit">
            登录
          </el-button>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { ElMessage } from 'element-plus'
import { Iphone, Lock } from '@element-plus/icons-vue'
import { sendCode as apiSendCode } from '../api/auth'

const auth = useAuthStore()
const router = useRouter()

const tab = ref('password')
const loading = ref(false)
const formRef = ref()
const form = reactive({ phone: '', password: '', code: '' })
const countdown = ref(0)
let timer = null

const rules = {
  phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
  code: [{ required: true, message: '请输入验证码', trigger: 'blur' }],
}

async function sendCode() {
  if (!form.phone) {
    ElMessage.warning('请先输入手机号')
    return
  }
  try {
    const res = await apiSendCode(form.phone)
    if (res && res.code) {
      ElMessage.success('验证码（开发模式）：' + res.code)
    } else {
      ElMessage.success('验证码已发送')
    }
    countdown.value = 60
    timer = setInterval(() => {
      countdown.value -= 1
      if (countdown.value <= 0) clearInterval(timer)
    }, 1000)
  } catch (e) {
    // 拦截器已提示
  }
}

async function onSubmit() {
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      if (tab.value === 'password') {
        await auth.login(form.phone, form.password)
      } else {
        await auth.loginSms(form.phone, form.code)
      }
      ElMessage.success('登录成功')
      router.push({ name: 'overview' })
    } catch (e) {
      // 拦截器已提示
    } finally {
      loading.value = false
    }
  })
}

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped>
.login-page {
  display: flex;
  min-height: 100vh;
}
.brand {
  flex: 1;
  background: var(--sidebar-gradient);
  color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 60px;
  gap: 16px;
}
.brand-title {
  font-size: 32px;
  font-weight: 700;
}
.brand-sub {
  letter-spacing: 2px;
  opacity: 0.8;
}
.chips {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 30px;
}
.chip {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: #fff;
}
.arrow {
  opacity: 0.7;
}
.brand-tip {
  margin-top: 24px;
  opacity: 0.65;
  font-size: 14px;
}
.form-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
}
.login-card {
  width: 380px;
}
.submit {
  width: 100%;
}
</style>
