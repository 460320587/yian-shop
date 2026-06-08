<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminCustomers, getAdminCustomerDetail, toggleAdminCustomerStatus } from '@/api/admin'
import { ElMessage } from 'element-plus'

const customers = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')
const vipFilter = ref('')
const detailVisible = ref(false)
const detail = ref<any>(null)

const vipOptions = [
  { label: '全部', value: '' },
  { label: 'VIP 1-3', value: '1' },
  { label: 'VIP 4', value: '4' },
  { label: 'VIP 5+', value: '5' },
]

async function loadCustomers() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    if (vipFilter.value) params.vip_level = vipFilter.value
    const res = await getAdminCustomers(params)
    customers.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
    ElMessage.error('加载客户失败')
  } finally {
    loading.value = false
  }
}

function onSearch() {
  currentPage.value = 1
  loadCustomers()
}

async function openDetail(row: any) {
  try {
    const res = await getAdminCustomerDetail(row.id)
    detail.value = res
    detailVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载客户详情失败')
  }
}

function formatVip(level: number): string {
  return level > 0 ? `VIP${level}` : '普通'
}

async function handleToggleStatus(row: any) {
  const action = row.status === 1 ? '禁用' : '启用'
  try {
    await toggleAdminCustomerStatus(row.id)
    ElMessage.success(`${action}成功`)
    loadCustomers()
  } catch (e) {
    console.error(e)
    ElMessage.error(`${action}失败`)
  }
}

onMounted(loadCustomers)
</script>

<template>
  <div class="customer-management">
    <div class="page-header">
      <h3>客户管理</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索手机号/昵称" clearable style="width: 260px" @keyup.enter="onSearch" />
        <el-select v-model="vipFilter" placeholder="VIP等级" clearable style="width: 140px" @change="onSearch">
          <el-option v-for="opt in vipOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
        <el-button type="primary" @click="onSearch">查询</el-button>
      </div>
    </div>
    <el-table :data="customers" v-loading="loading" stripe>
      <el-table-column prop="phone" label="手机号" min-width="140" />
      <el-table-column prop="nickname" label="昵称" min-width="120" />
      <el-table-column prop="real_name" label="真实姓名" min-width="120" />
      <el-table-column prop="company_name" label="公司" min-width="160" />
      <el-table-column label="VIP" width="100">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="scope.row.vip_level >= 5 ? 'danger' : scope.row.vip_level >= 3 ? 'warning' : 'info'">
            {{ formatVip(scope.row.vip_level) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="scope.row.status === 1 ? 'success' : 'danger'">
            {{ scope.row.status === 1 ? '正常' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="注册时间" min-width="160" />
      <el-table-column label="操作" width="180">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDetail(scope.row)">查看</el-button>
          <el-button v-if="scope?.row" link :type="scope.row.status === 1 ? 'danger' : 'success'" @click="handleToggleStatus(scope.row)">
            {{ scope.row.status === 1 ? '禁用' : '启用' }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadCustomers"
    />
    <el-dialog v-model="detailVisible" title="客户详情" width="560px">
      <div v-if="detail" class="detail-content">
        <p><strong>手机号：</strong>{{ detail.phone }}</p>
        <p><strong>昵称：</strong>{{ detail.nickname }}</p>
        <p><strong>真实姓名：</strong>{{ detail.real_name }}</p>
        <p><strong>公司：</strong>{{ detail.company_name }}</p>
        <p><strong>VIP等级：</strong>{{ formatVip(detail.vip_level) }}</p>
        <p><strong>余额：</strong>¥{{ (detail.balance / 100).toFixed(2) }}</p>
        <p><strong>注册时间：</strong>{{ detail.created_at }}</p>
        <div v-if="detail.addresses?.length" class="address-section">
          <h4>收货地址</h4>
          <p v-for="addr in detail.addresses" :key="addr.id">
            {{ addr.contact_name }} {{ addr.phone }} — {{ addr.address }}
          </p>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
.detail-content p { margin: 8px 0; }
.address-section { margin-top: 16px; }
.address-section h4 { margin-bottom: 8px; font-size: 14px; }
.address-section p { margin: 4px 0; font-size: 13px; color: #666; }
</style>
