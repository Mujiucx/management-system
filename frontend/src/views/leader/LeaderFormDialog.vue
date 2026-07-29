<template>
  <BaseDialog
    v-model="visible"
    :title="edit ? '编辑团队长' : '新建团队长'"
    width="520px"
  >
    <el-form :model="form" :rules="rules" ref="formRef" label-width="110px">
      <el-form-item label="姓名" prop="name">
        <el-input v-model="form.name" placeholder="请输入姓名" />
      </el-form-item>
      <el-form-item label="昵称">
        <el-input v-model="form.nickname" placeholder="可选" />
      </el-form-item>
      <el-form-item label="手机号" prop="phone">
        <el-input v-model="form.phone" placeholder="作为登录账号" />
      </el-form-item>
      <el-form-item label="所属机构" prop="institution_id" v-if="auth.isPlatform">
        <el-select v-model="form.institution_id" placeholder="请选择机构" style="width: 100%">
          <el-option v-for="o in orgs" :key="o.id" :label="o.short_name" :value="o.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="登录密码">
        <el-input
          v-model="form.password"
          type="password"
          placeholder="可选，默认 admin123"
          show-password
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="visible = false">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存</el-button>
    </template>
  </BaseDialog>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import BaseDialog from '../../components/BaseDialog.vue'
import { createLeader, updateLeader } from '../../api/leader'
import { listOrgs } from '../../api/org'
import { useAuthStore } from '../../store/auth'

const props = defineProps({
  modelValue: Boolean,
  editData: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])
const auth = useAuthStore()

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
const edit = computed(() => !!props.editData)
const formRef = ref()
const loading = ref(false)
const orgs = ref([])
const form = reactive({ name: '', nickname: '', phone: '', institution_id: '', password: '' })

const rules = {
  name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
  phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }],
  institution_id: [{ required: true, message: '请选择机构', trigger: 'change' }],
}

onMounted(async () => {
  if (auth.isPlatform) {
    orgs.value = (await listOrgs({ page: 1, page_size: 100 })).list
  }
})

watch(
  () => props.modelValue,
  (v) => {
    if (!v) return
    if (props.editData) {
      Object.assign(form, {
        name: props.editData.name,
        nickname: props.editData.nickname || '',
        phone: props.editData.phone,
        institution_id: props.editData.institution_id,
        password: '',
      })
    } else {
      Object.assign(form, {
        name: '',
        nickname: '',
        phone: '',
        institution_id: auth.isPlatform ? '' : auth.institution_id,
        password: '',
      })
    }
  },
)

async function submit() {
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      const payload = { ...form }
      if (!auth.isPlatform) payload.institution_id = auth.institution_id
      const res = edit.value
        ? await updateLeader(props.editData.id, payload)
        : await createLeader(payload)
      if (!edit.value && res.qr_link) {
        ElMessage.success('已生成邀请二维码：' + res.qr_link)
      } else {
        ElMessage.success('保存成功')
      }
      visible.value = false
      emit('saved')
    } finally {
      loading.value = false
    }
  })
}
</script>
