<template>
  <div class="data-table">
    <el-table :data="data" border stripe style="width: 100%">
      <el-table-column
        v-for="col in columns"
        :key="col.prop"
        :prop="col.prop"
        :label="col.label"
        :width="col.width"
        :min-width="col.minWidth"
      >
        <template #default="scope" v-if="col.slot">
          <slot :name="col.slot" :row="scope.row" :index="scope.$index" />
        </template>
      </el-table-column>
    </el-table>

    <div class="pager" v-if="total > 0">
      <el-pagination
        :current-page="page"
        :page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next"
        @current-change="(p) => emit('page-change', { page: p, pageSize })"
        @size-change="(s) => emit('page-change', { page: 1, pageSize: s })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  page: { type: Number, default: 1 },
  pageSize: { type: Number, default: 20 },
})
const emit = defineEmits(['page-change'])
</script>

<style scoped>
.pager {
  margin-top: 14px;
  display: flex;
  justify-content: flex-end;
}
</style>
