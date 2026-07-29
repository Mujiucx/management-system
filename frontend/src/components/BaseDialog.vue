<template>
  <el-dialog
    v-model="visible"
    :title="title"
    :width="width"
    @close="onClose"
  >
    <slot />
    <template #footer v-if="$slots.footer">
      <slot name="footer" />
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  width: { type: String, default: '520px' },
})
const emit = defineEmits(['update:modelValue', 'close'])

const visible = ref(props.modelValue)
watch(
  () => props.modelValue,
  (v) => {
    visible.value = v
  },
)
watch(visible, (v) => emit('update:modelValue', v))

function onClose() {
  emit('close')
}
</script>
