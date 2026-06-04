<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminOrders } from '@/api/admin'

const orders = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')

async function loadOrders() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    const res = await getAdminOrders(params)
    orders.value = res.data
    total.value = res.total
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function onSearch() { currentPage.value = 1; loadOrders() }
onMounted(loadOrders)
</script>

<template>
  <div class="order-management">
    <div class="page-header">
      <h3>订单管理</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索订单号/客户" clearable style="width: 240px" @keyup.enter="onSearch" />
        <el-button type="primary" @click="onSearch">查询</el-button>
      </div>
    </div>
    <el-table :data="orders" v-loading="loading" stripe>
      <el-table-column prop="order_no" label="订单号" />
      <el-table-column prop="customer_name" label="客户" />
      <el-table-column prop="total_amount" label="金额" />
      <el-table-column prop="customer_status" label="状态" />
      <el-table-column prop="created_at" label="创建时间" />
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadOrders" />
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
