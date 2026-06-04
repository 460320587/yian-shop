<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { getAdminAuditLogs, getAdminAuditLogDetail } from '@/api/admin'

const logs = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const actionFilter = ref('')
const detailVisible = ref(false)
const detail = ref<any>(null)

const actionOptions = [
  { value: 'login', label: '登录' },
  { value: 'logout', label: '登出' },
  { value: 'create', label: '创建' },
  { value: 'update', label: '更新' },
  { value: 'delete', label: '删除' },
]

async function loadLogs() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (actionFilter.value) params.action = actionFilter.value
    const res = await getAdminAuditLogs(params)
    logs.value = res.data
    total.value = res.total
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

async function showDetail(row: any) {
  try {
    detail.value = await getAdminAuditLogDetail(row.id)
    detailVisible.value = true
  } catch (e) { console.error(e) }
}

onMounted(loadLogs)
</script>

<template>
  <div class="audit-log-view">
    <div class="page-header">
      <h3>审计日志</h3>
      <el-select v-model="actionFilter" placeholder="操作类型" clearable style="width: 140px" @change="loadLogs">
        <el-option v-for="opt in actionOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
      </el-select>
    </div>
    <el-table :data="logs" v-loading="loading" stripe>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="admin_name" label="操作人" />
      <el-table-column prop="action" label="操作" />
      <el-table-column prop="model_type" label="对象类型" />
      <el-table-column prop="model_id" label="对象ID" width="80" />
      <el-table-column prop="ip" label="IP" />
      <el-table-column prop="result" label="结果" width="80">
        <template #default="scope"><el-tag v-if="scope?.row" :type="scope.row.result === 1 ? 'success' : 'danger'">{{ scope.row.result === 1 ? '成功' : '失败' }}</el-tag></template>
      </el-table-column>
      <el-table-column prop="created_at" label="时间" />
      <el-table-column label="操作" width="80">
        <template #default="scope"><el-button v-if="scope?.row" link type="primary" @click="showDetail(scope.row)">详情</el-button></template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadLogs" />
    <el-dialog v-model="detailVisible" title="日志详情" width="560px">
      <div v-if="detail" class="detail-content">
        <p><strong>ID:</strong> {{ detail.id }}</p>
        <p><strong>操作人:</strong> {{ detail.admin_name }}</p>
        <p><strong>操作:</strong> {{ detail.action }}</p>
        <p><strong>对象:</strong> {{ detail.model_type }}#{{ detail.model_id }}</p>
        <p><strong>IP:</strong> {{ detail.ip }}</p>
        <p><strong>UA:</strong> {{ detail.user_agent }}</p>
        <p><strong>结果:</strong> {{ detail.result === 1 ? '成功' : '失败' }}</p>
        <p v-if="detail.before_data"><strong>操作前:</strong> {{ JSON.stringify(detail.before_data) }}</p>
        <p v-if="detail.after_data"><strong>操作后:</strong> {{ JSON.stringify(detail.after_data) }}</p>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.pagination { margin-top: 20px; justify-content: flex-end; }
.detail-content p { margin: 8px 0; font-size: 14px; }
</style>
