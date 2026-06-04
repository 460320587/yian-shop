<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminAfterSales, auditAfterSale } from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const afterSales = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const statusFilter = ref('')

const statusOptions = [
  { label: '全部', value: '' },
  { label: '待审核', value: '11' },
  { label: '已通过', value: '12' },
  { label: '已拒绝', value: '13' },
]

const typeMap: Record<number, string> = { 1: '退货退款', 2: '换货', 3: '补印', 4: '优惠货款', 5: '其他' }

async function loadAfterSales() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (statusFilter.value) params.status = statusFilter.value
    const res = await getAdminAfterSales(params)
    afterSales.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
    ElMessage.error('加载售后单失败')
  } finally {
    loading.value = false
  }
}

function onSearch() {
  currentPage.value = 1
  loadAfterSales()
}

async function handleAudit(row: any, status: number) {
  try {
    await ElMessageBox.confirm(`确认${status === 12 ? '通过' : '拒绝'}？`, '提示', { type: 'warning' })
    await auditAfterSale(row.id, { status })
    ElMessage.success('审核成功')
    loadAfterSales()
  } catch { }
}

function formatType(type: number): string {
  return typeMap[type] || '其他'
}

function formatAmount(amount: number): string {
  return '¥' + (amount / 100).toFixed(2)
}

onMounted(loadAfterSales)
</script>

<template>
  <div class="aftersale-management">
    <div class="page-header">
      <h3>售后管理</h3>
      <div class="filter-bar">
        <el-select v-model="statusFilter" placeholder="售后状态" clearable style="width: 140px" @change="onSearch">
          <el-option v-for="opt in statusOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
      </div>
    </div>
    <el-table :data="afterSales" v-loading="loading" stripe>
      <el-table-column prop="after_sale_no" label="售后单号" min-width="160" />
      <el-table-column prop="order_no" label="订单号" min-width="160" />
      <el-table-column label="类型" width="100">
        <template #default="scope"><span v-if="scope?.row">{{ formatType(scope.row.type) }}</span></template>
      </el-table-column>
      <el-table-column prop="reason" label="原因" min-width="140" />
      <el-table-column label="金额" width="100">
        <template #default="scope"><span v-if="scope?.row">{{ formatAmount(scope.row.amount) }}</span></template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="scope.row.status === 11 ? 'warning' : scope.row.status === 12 ? 'success' : 'danger'">
            {{ scope.row.status === 11 ? '待审核' : scope.row.status === 12 ? '已通过' : '已拒绝' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" min-width="160" />
      <el-table-column label="操作" width="140">
        <template #default="scope">
          <template v-if="scope?.row && scope.row.status === 11">
            <el-button link type="success" @click="handleAudit(scope.row, 12)">通过</el-button>
            <el-button link type="danger" @click="handleAudit(scope.row, 13)">拒绝</el-button>
          </template>
          <span v-else>-</span>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadAfterSales" />
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
