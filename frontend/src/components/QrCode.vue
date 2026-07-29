<template>
  <div class="qr">
    <img v-if="dataUrl" :src="dataUrl" :width="size" :height="size" alt="二维码" />
    <span v-else class="placeholder">无二维码</span>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import QRCode from 'qrcode'

const props = defineProps({
  value: { type: String, default: '' },
  size: { type: Number, default: 160 },
})
const dataUrl = ref('')

function generate() {
  if (!props.value) {
    dataUrl.value = ''
    return
  }
  QRCode.toDataURL(props.value, { width: props.size, margin: 1 })
    .then((url) => {
      dataUrl.value = url
    })
    .catch(() => {
      dataUrl.value = ''
    })
}

onMounted(generate)
watch(() => props.value, generate)
</script>

<style scoped>
.placeholder {
  color: #999;
  font-size: 13px;
}
</style>
