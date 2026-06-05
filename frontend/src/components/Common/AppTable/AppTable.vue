<script setup lang="ts">
/**
 * 通用表格组件
 * - 封装 Element Plus Table + Pagination
 * - 支持 loading、分页、多选、行点击
 */
import { computed } from 'vue'

export interface TableColumn {
  prop?: string
  label?: string
  width?: string | number
  minWidth?: string | number
  fixed?: 'left' | 'right'
  sortable?: boolean
  type?: 'selection' | 'index' | 'expand'
  slot?: string
}

export interface PaginationConfig {
  currentPage: number
  pageSize: number
  total: number
}

const props = withDefaults(
  defineProps<{
    columns: TableColumn[]
    data: any[]
    loading?: boolean
    selectable?: boolean
    pagination?: PaginationConfig | null
  }>(),
  {
    loading: false,
    selectable: false,
    pagination: null,
  },
)

const emit = defineEmits<{
  (e: 'row-click', row: any): void
  (e: 'selection-change', rows: any[]): void
  (e: 'page-change', page: number): void
  (e: 'size-change', size: number): void
}>()

const showPagination = computed(() => props.pagination !== null)

function handleRowClick(row: any) {
  emit('row-click', row)
}

function handleSelectionChange(rows: any[]) {
  emit('selection-change', rows)
}

function handleCurrentChange(page: number) {
  emit('page-change', page)
}

function handleSizeChange(size: number) {
  emit('size-change', size)
}
</script>

<template>
  <div class="app-table-wrapper">
    <el-table
      v-loading="loading"
      :data="data"
      style="width: 100%"
      @row-click="handleRowClick"
      @selection-change="handleSelectionChange"
    >
      <el-table-column
        v-for="col in columns"
        :key="col.prop || col.type"
        :type="col.type"
        :prop="col.prop"
        :label="col.label"
        :width="col.width"
        :min-width="col.minWidth"
        :fixed="col.fixed"
        :sortable="col.sortable"
      >
        <template #default="scope" v-if="col.slot">
          <slot :name="col.slot" :row="scope.row" :$index="scope.$index" />
        </template>
      </el-table-column>
    </el-table>

    <div v-if="showPagination" class="pagination-wrapper">
      <el-pagination
        v-model:current-page="pagination!.currentPage"
        v-model:page-size="pagination!.pageSize"
        :total="pagination!.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handleCurrentChange"
        @size-change="handleSizeChange"
      />
    </div>
  </div>
</template>

<style scoped>
.app-table-wrapper {
  width: 100%;
}
.pagination-wrapper {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}
</style>
