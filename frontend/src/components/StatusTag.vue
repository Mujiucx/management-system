<template>
  <el-tag v-if="value === 'active'" type="success">启用</el-tag>
  <el-tag v-else type="info">禁用</el-tag>
</template>

<script setup>
defineProps({
  value: { type: String, default: 'active' },
})
</script>
