<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminInkCoverageChecks,
  createAdminInkCoverageCheck,
  updateAdminInkCoverageCheck,
  deleteAdminInkCoverageCheck,
} from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const checks = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const form = reactive<any>({
  id: null,
  order_id: null,
  file_id: null,
  check_type: 1,
  ink_type: 'CMYK',
  coverage_c: 0,
  coverage_m: 0,
  coverage_y: 0,
  coverage_k: 0,
  total_coverage: 0,
  check_result: 1,
  check_report: null,
})

function resetForm() {
  form.id = null
  form.order_id = null
  form.file_id = null
  form.check_type = 1
  form.ink_type = 'CMYK'
  form.coverage_c = 0
  form.coverage_m = 0
  form.coverage_y = 0
  form.coverage_k = 0
  form.total_coverage = 0
  form.check_result = 1
  form.check_report = null
}

function openCreate() {
  resetForm()
  isEdit.value = false
  dialogVisible.value = true
}

function openEdit(row: any) {
  Object.assign(form, row)
  isEdit.value = true
  dialogVisible.value = true
}

async function handleSave() {
  try {
    if (isEdit.value && form.id) {
      await updateAdminInkCoverageCheck(form.id, {
        ink_type: form.ink_type,
        coverage_c: form.coverage_c,
        coverage_m: form.coverage_m,
        coverage_y: form.coverage_y,
        coverage_k: form.coverage_k,
        total_coverage: form.total_coverage,
        check_result: form.check_result,
        check_report: form.check_report,
      })
      ElMessage.success('更新成功')
    } else {
      await createAdminInkCoverageCheck({
        order_id: form.order_id,
        file_id: form.file_id,
        check_type: form.check_type,
        ink_type: form.ink_type,
        coverage_c: form.coverage_c,
        coverage_m: form.coverage_m,
        coverage_y: form.coverage_y,
        coverage_k: form.coverage_k,
        total_coverage: form.total_coverage,
        check_result: form.check_result,
        check_report: form.check_report,
      })
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadChecks()
  } catch (e) {
    console.error(e)
  }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确认删除该检测记录？', '提示', { type: 'warning' })
    await deleteAdminInkCoverageCheck(row.id)
    ElMessage.success('删除成功')
    loadChecks()
  } catch {
    // user cancelled
  }
}

function resultText(result: number | null): string {
  if (result === null) return '-'
  return result === 1 ? '通过' : '未通过'
}

function resultType(result: number | null): string {
  if (result === null) return 'info'
  return result === 1 ? 'success' : 'danger'
}

async function loadChecks() {
  loading.value = true
  try {
    const res = await getAdminInkCoverageChecks({
      page: currentPage.value,
      per_page: pageSize.value,
    })
    checks.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadChecks)
</script>

<template>
  <div class="ink-check-management">
    <div class="page-header">
      <h3>印前检查管理</h3>
      <el-button data-testid="create-btn" type="primary" @click="openCreate">新建检测</el-button>
    </div>

    <el-table :data="checks" v-loading="loading" stripe>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="order_id" label="订单ID" width="80" />
      <el-table-column prop="check_type" label="检查类型" width="90">
        <template #default="scope">
          <span v-if="scope?.row">{{ scope.row.check_type === 1 ? '预检' : scope.row.check_type === 2 ? '正式' : '复核' }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="ink_type" label="油墨类型" width="90" />
      <el-table-column label="覆盖率">
        <template #default="scope">
          <span v-if="scope?.row" class="coverage-text">
            C:{{ scope.row.coverage_c ?? '-' }} M:{{ scope.row.coverage_m ?? '-' }}
            Y:{{ scope.row.coverage_y ?? '-' }} K:{{ scope.row.coverage_k ?? '-' }}
          </span>
        </template>
      </el-table-column>
      <el-table-column prop="total_coverage" label="总覆盖率" width="90">
        <template #default="scope">
          <span v-if="scope?.row">{{ scope.row.total_coverage ?? '-' }}%</span>
        </template>
      </el-table-column>
      <el-table-column label="结果" width="80">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="resultType(scope.row.check_result)" size="small">
            {{ resultText(scope.row.check_result) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="checked_at" label="检查时间" width="160" />
      <el-table-column label="操作" width="140">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openEdit(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row" link type="danger" @click="handleDelete(scope.row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :total="total"
      layout="total, prev, pager, next"
      class="pagination"
      @change="loadChecks"
    />

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑检测记录' : '新建检测记录'" width="560px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="订单ID">
          <el-input-number v-model="form.order_id" :min="1" :disabled="isEdit" />
        </el-form-item>
        <el-form-item label="文件ID">
          <el-input-number v-model="form.file_id" :min="1" :disabled="isEdit" />
        </el-form-item>
        <el-form-item label="检查类型">
          <el-select v-model="form.check_type" :disabled="isEdit" style="width: 160px">
            <el-option :value="1" label="预检" />
            <el-option :value="2" label="正式" />
            <el-option :value="3" label="复核" />
          </el-select>
        </el-form-item>
        <el-form-item label="油墨类型">
          <el-input v-model="form.ink_type" style="width: 160px" />
        </el-form-item>
        <el-form-item label="C">
          <el-input-number v-model="form.coverage_c" :min="0" :max="100" :precision="2" />
        </el-form-item>
        <el-form-item label="M">
          <el-input-number v-model="form.coverage_m" :min="0" :max="100" :precision="2" />
        </el-form-item>
        <el-form-item label="Y">
          <el-input-number v-model="form.coverage_y" :min="0" :max="100" :precision="2" />
        </el-form-item>
        <el-form-item label="K">
          <el-input-number v-model="form.coverage_k" :min="0" :max="100" :precision="2" />
        </el-form-item>
        <el-form-item label="总覆盖率">
          <el-input-number v-model="form.total_coverage" :min="0" :max="100" :precision="2" />
        </el-form-item>
        <el-form-item label="结果">
          <el-radio-group v-model="form.check_result">
            <el-radio :value="1">通过</el-radio>
            <el-radio :value="0">未通过</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-header h3 {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}
.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
.coverage-text {
  font-size: 12px;
  color: #606266;
}
</style>
