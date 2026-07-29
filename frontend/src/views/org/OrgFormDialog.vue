<template>
  <BaseDialog
    v-model="visible"
    :title="edit ? '编辑机构' : '新建机构'"
    width="560px"
    @close="reset"
  >
    <el-form :model="form" :rules="rules" ref="formRef" label-width="130px">
      <el-form-item label="企业全称" prop="full_name">
        <el-input v-model="form.full_name" placeholder="请输入企业全称" />
      </el-form-item>
      <el-form-item label="企业简称" prop="short_name">
        <el-input v-model="form.short_name" placeholder="请输入企业简称" />
      </el-form-item>
      <el-form-item label="营业执照">
        <el-upload
          :http-request="customUpload"
          :show-file-list="false"
          accept=".jpg,.jpeg,.png,.pdf"
        >
          <el-button type="primary">上传营业执照</el-button>
        </el-upload>
        <div v-if="form.license_path" class="file-tip">{{ form.license_path }}</div>
      </el-form-item>
      <el-form-item label="统一社会信用代码" prop="license_no">
        <el-input v-model="form.license_no" placeholder="请输入统一社会信用代码" />
      </el-form-item>
      <el-form-item label="联系人" prop="contact_name">
        <el-input v-model="form.contact_name" placeholder="请输入联系人姓名" />
      </el-form-item>
      <el-form-item label="联系人手机号" prop="contact_phone">
        <el-input v-model="form.contact_phone" placeholder="作为登录账号" />
      </el-form-item>
      <el-form-item label="登录密码">
        <el-input
          v-model="form.admin_password"
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
import { ref, reactive, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import BaseDialog from '../../components/BaseDialog.vue'
import { createOrg, updateOrg, uploadFile } from '../../api/org'

const props = defineProps({
  modelValue: Boolean,
  editData: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
const edit = computed(() => !!props.editData)
const formRef = ref()
const loading = ref(false)
const form = reactive({
  full_name: '',
  short_name: '',
  license_path: '',
  license_no: '',
  contact_name: '',
  contact_phone: '',
  admin_password: '',
})

const rules = {
  full_name: [{ required: true, message: '请输入企业全称', trigger: 'blur' }],
  short_name: [{ required: true, message: '请输入企业简称', trigger: 'blur' }],
  license_no: [{ required: true, message: '请输入信用代码', trigger: 'blur' }],
  contact_phone: [{ required: true, message: '请输入联系人手机号', trigger: 'blur' }],
}

watch(
  () => props.modelValue,
  (v) => {
    if (!v) return
    if (props.editData) {
      Object.assign(form, {
        full_name: props.editData.full_name,
        short_name: props.editData.short_name,
        license_path: props.editData.license_path || '',
        license_no: props.editData.license_no,
        contact_name: props.editData.contact_name || '',
        contact_phone: props.editData.contact_phone,
        admin_password: '',
      })
    } else {
      reset()
    }
  },
)

async function customUpload({ file }) {
  const res = await uploadFile(file, 'org')
  form.license_path = res.url
  ElMessage.success('上传成功')
}

function reset() {
  Object.assign(form, {
    full_name: '',
    short_name: '',
    license_path: '',
    license_no: '',
    contact_name: '',
    contact_phone: '',
    admin_password: '',
  })
}

async function submit() {
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      if (edit.value) {
        await updateOrg(props.editData.id, { ...form })
      } else {
        await createOrg({ ...form })
      }
      ElMessage.success('保存成功')
      visible.value = false
      emit('saved')
    } finally {
      loading.value = false
    }
  })
}
</script>

<style scoped>
.file-tip {
  margin-top: 8px;
  color: var(--color-success);
  font-size: 12px;
}
</style>
