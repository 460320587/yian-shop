<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminCustomers } from '@/api/admin'

const customers = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')

async function loadCustomers() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    const res = await getAdminCustomers(params)
    customers.value = res.data
    total.value = res.total
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function onSearch() { currentPage.value = 1; loadCustomers() }
onMounted(loadCustomers)
</script>

<template>
  <div class="customer-management">
    <div class="page-header">
      <h3>客户管理</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索手机号/公司名" clearable style="width: 260px" @keyup.enter="onSearch" />
        <el-button type="primary" @click="onSearch">查询</el-button>
      </div>
    </div>
    <el-table :data="customers" v-loading="loading" stripe>
      <el-table-column prop="phone" label="手机号" />
      <el-table-column prop="name" label="联系人" />
      <el-table-column prop="company_name" label="公司" />
      <el-table-column prop="status" label="状态" />
      <el-table-column prop="created_at" label="注册时间" />
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadCustomers" />
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
