<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminProductionSchedules, getAdminProductionScheduleDetail,
  createAdminProductionSchedule, updateAdminProductionSchedule, updateAdminProductionScheduleProgress,
} from '@/api/admin'
import { ElMessage } from 'element-plus'

const schedules = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const statusFilter = ref('')
const keyword = ref('')

const statusOptions = [
  { label: '全部', value: '' },
  { label: '待排期', value: 0 },
  { label: '待开始', value: 1 },
  { label: '生产中', value: 2 },
  { label: '已完成', value: 3 },
  { label: '延期', value: 4 },
]

const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)

const form = reactive({
  order_id: null as number | null,
  schedule_date: '',
  process_name: '',
  priority: 3,
  estimated_hours: 0,
})

const detailVisible = ref(false)
const detail = ref<any>(null)

const progressDialogVisible = ref(false)
const progressForm = reactive({ progress: 0, actual_hours: 0 as number | null })
const progressId = ref<number | null>(null)

async function loadList() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (statusFilter.value !== '') params.status = Number(statusFilter.value)
    if (keyword.value) params.process_name = keyword.value
    const res = await getAdminProductionSchedules(params)
    schedules.value = res.data
    total.value = res.total
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function onSearch() { currentPage.value = 1; loadList() }

function openCreate() {
  isEdit.value = false; editId.value = null
  form.order_id = null; form.schedule_date = ''; form.process_name = ''; form.priority = 3; form.estimated_hours = 0
  dialogVisible.value = true
}

async function openEdit(row: any) {
  isEdit.value = true; editId.value = row.id
  form.order_id = row.order_id
  form.schedule_date = row.schedule_date
  form.process_name = row.process_name
  form.priority = row.priority
  form.estimated_hours = row.estimated_hours ?? 0
  dialogVisible.value = true
}

async function handleSave() {
  try {
    if (isEdit.value && editId.value) {
      await updateAdminProductionSchedule(editId.value, {
        schedule_date: form.schedule_date,
        process_name: form.process_name,
        priority: form.priority,
        estimated_hours: form.estimated_hours,
      })
      ElMessage.success('更新成功')
    } else {
      await createAdminProductionSchedule({
        order_id: form.order_id!,
        schedule_date: form.schedule_date,
        process_name: form.process_name,
        priority: form.priority,
        estimated_hours: form.estimated_hours,
      })
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadList()
  } catch (e) { console.error(e) }
}

async function openDetail(row: any) {
  try {
    const res = await getAdminProductionScheduleDetail(row.id)
    detail.value = res
    detailVisible.value = true
  } catch (e) { console.error(e) }
}

function openProgressDialog(row: any) {
  progressId.value = row.id
  progressForm.progress = row.progress
  progressForm.actual_hours = row.actual_hours
  progressDialogVisible.value = true
}

async function handleSaveProgress() {
  if (!progressId.value) return
  try {
    await updateAdminProductionScheduleProgress(progressId.value, {
      progress: progressForm.progress,
      actual_hours: progressForm.actual_hours ?? undefined,
    })
    ElMessage.success('进度更新成功')
    progressDialogVisible.value = false
    loadList()
  } catch (e) { console.error(e) }
}

function statusText(status: number): string {
  const map: Record<number, string> = { 0: '待排期', 1: '待开始', 2: '生产中', 3: '已完成', 4: '延期' }
  return map[status] ?? '未知'
}

function statusType(status: number): string {
  const map: Record<number, string> = { 0: 'info', 1: '', 2: 'warning', 3: 'success', 4: 'danger' }
  return map[status] ?? ''
}

function priorityText(priority: number): string {
  const map: Record<number, string> = { 1: '紧急', 2: '高', 3: '中', 4: '低', 5: '最低' }
  return map[priority] ?? '中'
}

function priorityType(priority: number): string {
  const map: Record<number, string> = { 1: 'danger', 2: 'warning', 3: 'primary', 4: 'info', 5: '' }
  return map[priority] ?? ''
}

onMounted(loadList)
</script>

<template>
  <div class="schedule-management">
    <div class="page-header">
      <h3>生产排期</h3>
      <div class="filter-bar">
        <el-select v-model="statusFilter" placeholder="状态" clearable style="width: 130px" @change="onSearch">
          <el-option v-for="opt in statusOptions" :key="String(opt.value)" :label="opt.label" :value="opt.value" />
        </el-select>
        <el-input v-model="keyword" placeholder="搜索工序名称" clearable style="width: 220px" @keyup.enter="onSearch" />
        <el-button type="primary" @click="onSearch">查询</el-button>
        <el-button type="primary" @click="openCreate">新建排期</el-button>
      </div>
    </div>
    <el-table :data="schedules" v-loading="loading" stripe>
      <el-table-column prop="order.order_no" label="订单号" min-width="150">
        <template #default="scope">
          <span v-if="scope?.row?.order">{{ scope.row.order.order_no }}</span>
          <span v-else class="text-gray">—</span>
        </template>
      </el-table-column>
      <el-table-column prop="process_name" label="工序" min-width="120" />
      <el-table-column prop="schedule_date" label="排期日期" min-width="120" />
      <el-table-column label="优先级" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="priorityType(scope.row.priority)" size="small">{{ priorityText(scope.row.priority) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="预计工时" width="100">
        <template #default="scope"><span v-if="scope?.row">{{ scope.row.estimated_hours }}h</span></template>
      </el-table-column>
      <el-table-column label="进度" width="160">
        <template #default="scope">
          <el-progress v-if="scope?.row" :percentage="scope.row.progress" :status="scope.row.progress === 100 ? 'success' : undefined" />
        </template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="statusType(scope.row.status)">{{ statusText(scope.row.status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="220">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDetail(scope.row)">详情</el-button>
          <el-button v-if="scope?.row" link type="primary" @click="openEdit(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row && scope.row.status !== 3" link type="warning" @click="openProgressDialog(scope.row)">进度</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadList" />

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑排期' : '新建排期'" width="520px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="订单ID" v-if="!isEdit"><el-input-number v-model="form.order_id" :min="1" style="width: 100%" /></el-form-item>
        <el-form-item label="排期日期"><el-date-picker v-model="form.schedule_date" type="date" value-format="YYYY-MM-DD" style="width: 100%" /></el-form-item>
        <el-form-item label="工序名称"><el-input v-model="form.process_name" /></el-form-item>
        <el-form-item label="优先级">
          <el-select v-model="form.priority" style="width: 100%">
            <el-option v-for="opt in statusOptions.slice(1)" :key="opt.value" :label="opt.label" :value="opt.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="预计工时"><el-input-number v-model="form.estimated_hours" :min="0" :precision="1" style="width: 100%" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible = false">取消</el-button><el-button type="primary" @click="handleSave">保存</el-button></template>
    </el-dialog>

    <el-dialog v-model="progressDialogVisible" title="更新进度" width="400px">
      <el-form :model="progressForm" label-width="100px">
        <el-form-item label="进度"><el-slider v-model="progressForm.progress" :max="100" show-stops /></el-form-item>
        <el-form-item label="实际工时"><el-input-number v-model="progressForm.actual_hours" :min="0" :precision="1" style="width: 100%" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="progressDialogVisible = false">取消</el-button><el-button type="primary" @click="handleSaveProgress">保存</el-button></template>
    </el-dialog>

    <el-drawer v-model="detailVisible" title="排期详情" size="480px">
      <div v-if="detail" class="detail-content">
        <p><strong>订单号：</strong>{{ detail.order?.order_no || '—' }}</p>
        <p><strong>工序：</strong>{{ detail.process_name }}</p>
        <p><strong>排期日期：</strong>{{ detail.schedule_date }}</p>
        <p><strong>优先级：</strong>{{ priorityText(detail.priority) }}</p>
        <p><strong>预计工时：</strong>{{ detail.estimated_hours }}h</p>
        <p><strong>实际工时：</strong>{{ detail.actual_hours ?? '—' }}h</p>
        <p><strong>进度：</strong>{{ detail.progress }}%</p>
        <p><strong>状态：</strong><el-tag :type="statusType(detail.status)">{{ statusText(detail.status) }}</el-tag></p>
        <p v-if="detail.delay_reason"><strong>延期原因：</strong>{{ detail.delay_reason }}</p>
        <p><strong>创建时间：</strong>{{ detail.created_at }}</p>
      </div>
    </el-drawer>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
.detail-content p { margin: 10px 0; }
.text-gray { color: #909399; }
</style>
