<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminAfterSales, auditAfterSale } from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const afterSales = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)

async function loadAfterSales() {
  loading.value = true
  try { const res = await getAdminAfterSales({ page: currentPage.value, per_page: pageSize.value }); afterSales.value = res.data; total.value = res.total }
  catch (e) { console.error(e) } finally { loading.value = false }
}

async function handleAudit(row: any, status: number) {
  try { await ElMessageBox.confirm(`确认${status === 12 ? '通过' : '拒绝'}？`, '提示', { type: 'warning' }); await auditAfterSale(row.id, { status }); ElMessage.success('审核成功'); loadAfterSales() } catch { }
}

onMounted(loadAfterSales)
</script>

<template>
  <div class="aftersale-management">
    <div class="page-header"><h3>售后管理</h3></div>
    <el-table :data="afterSales" v-loading="loading" stripe>
      <el-table-column prop="order_no" label="订单号" />
      <el-table-column prop="type" label="类型" />
      <el-table-column prop="reason" label="原因" />
      <el-table-column prop="amount" label="金额" />
      <el-table-column prop="status" label="状态" />
      <el-table-column label="操作"><template #default="scope"><template v-if="scope?.row && scope.row.status === 11"><el-button link type="success" @click="handleAudit(scope.row, 12)">通过</el-button><el-button link type="danger" @click="handleAudit(scope.row, 13)">拒绝</el-button></template><span v-else>-</span></template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadAfterSales" />
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
