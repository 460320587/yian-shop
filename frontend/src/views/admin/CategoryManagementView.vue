<script setup lang="ts">
import { ref, onMounted, computed, reactive } from 'vue'
import {
  getAdminCategories,
  createAdminCategory,
  updateAdminCategory,
  toggleAdminCategoryStatus,
  deleteAdminCategory,
} from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const categories = ref<any[]>([])
const loading = ref(false)
const keyword = ref('')
const statusFilter = ref<number | null>(null)
const dialogVisible = ref(false)
const isEdit = ref(false)
const currentId = ref<number | null>(null)

const form = reactive({
  name: '',
  parent_id: 0,
  icon: '',
  sort: 0,
})

const levelOptions = [
  { label: '全部', value: null },
  { label: '一级', value: 1 },
  { label: '二级', value: 2 },
  { label: '三级', value: 3 },
]

const parentOptions = computed(() => {
  return [
    { label: '顶级分类', value: 0 },
    ...categories.value
      .filter((c) => c.status === 1 && c.level < 3)
      .map((c) => ({ label: c.name, value: c.id })),
  ]
})

const filteredCategories = computed(() => {
  let list = categories.value
  if (keyword.value.trim()) {
    const k = keyword.value.trim().toLowerCase()
    list = list.filter((c) => c.name.toLowerCase().includes(k))
  }
  if (statusFilter.value !== null && statusFilter.value !== undefined) {
    list = list.filter((c) => c.status === statusFilter.value)
  }
  return list
})

async function loadCategories() {
  loading.value = true
  try {
    const res = await getAdminCategories()
    categories.value = res || []
  } catch (e) {
    console.error(e)
    ElMessage.error('加载分类失败')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.name = ''
  form.parent_id = 0
  form.icon = ''
  form.sort = 0
}

function openDialog(category?: any) {
  dialogVisible.value = true
  if (category) {
    isEdit.value = true
    currentId.value = category.id
    form.name = category.name
    form.parent_id = category.parent_id
    form.icon = category.icon || ''
    form.sort = category.sort
  } else {
    isEdit.value = false
    currentId.value = null
    resetForm()
  }
}

function closeDialog() {
  dialogVisible.value = false
  resetForm()
}

async function submitForm() {
  if (!form.name.trim()) {
    ElMessage.warning('请输入分类名称')
    return
  }
  try {
    if (isEdit.value && currentId.value) {
      const payload: any = {}
      if (form.name.trim()) payload.name = form.name.trim()
      if (form.icon.trim()) payload.icon = form.icon.trim()
      if (form.sort !== undefined) payload.sort = form.sort
      await updateAdminCategory(currentId.value, payload)
      ElMessage.success('更新成功')
    } else {
      await createAdminCategory({
        name: form.name.trim(),
        parent_id: form.parent_id > 0 ? form.parent_id : undefined,
        icon: form.icon.trim() || undefined,
        sort: form.sort ?? undefined,
      })
      ElMessage.success('创建成功')
    }
    closeDialog()
    await loadCategories()
  } catch (e) {
    console.error(e)
  }
}

async function handleToggleStatus(row: any) {
  try {
    await toggleAdminCategoryStatus(row.id)
    ElMessage.success('状态已更新')
    loadCategories()
  } catch (e) {
    console.error(e)
  }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定删除该分类吗？', '提示', { type: 'warning' })
    await deleteAdminCategory(row.id)
    ElMessage.success('删除成功')
    loadCategories()
  } catch (e: any) {
    if (e !== 'cancel') {
      const msg = e?.response?.data?.message || '删除失败'
      if (msg.includes('商品') || msg.includes('子分类')) {
        ElMessage.error('该分类下存在商品或子分类，无法删除')
      }
    }
  }
}

function levelText(level: number): string {
  const map: Record<number, string> = { 1: '一级', 2: '二级', 3: '三级' }
  return map[level] ?? '未知'
}

function parentName(parentId: number): string {
  if (parentId === 0) return '-'
  const parent = categories.value.find((c) => c.id === parentId)
  return parent?.name ?? '-'
}

onMounted(loadCategories)

defineExpose({
  categories,
  loading,
  keyword,
  statusFilter,
  dialogVisible,
  isEdit,
  form,
  filteredCategories,
  parentOptions,
  loadCategories,
  openDialog,
  closeDialog,
  submitForm,
  handleToggleStatus,
  handleDelete,
  levelText,
  parentName,
})
</script>

<template>
  <div class="category-management">
    <div class="page-header">
      <h3>商品分类管理</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索分类名称" clearable style="width: 200px" @keyup.enter="loadCategories" />
        <el-select v-model="statusFilter" placeholder="状态筛选" clearable style="width: 140px" @change="loadCategories">
          <el-option :value="1" label="启用" />
          <el-option :value="0" label="禁用" />
        </el-select>
        <el-button type="primary" @click="loadCategories">查询</el-button>
        <el-button type="success" @click="openDialog()">+ 新建分类</el-button>
      </div>
    </div>

    <el-table :data="filteredCategories" v-loading="loading" stripe>
      <el-table-column prop="id" label="ID" width="70" />
      <el-table-column label="图标" width="70">
        <template #default="scope">
          <img v-if="scope?.row?.icon" :src="scope.row.icon" class="category-icon" />
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column prop="name" label="名称" min-width="150" />
      <el-table-column label="层级" width="80">
        <template #default="scope">
          <span v-if="scope?.row">{{ levelText(scope.row.level) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="父分类" width="120">
        <template #default="scope">
          <span v-if="scope?.row">{{ parentName(scope.row.parent_id) }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="sort" label="排序" width="80" />
      <el-table-column label="状态" width="90">
        <template #default="scope">
          <el-tag v-if="scope?.row?.status === 1" type="success" size="small">启用</el-tag>
          <el-tag v-else type="info" size="small">禁用</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="220">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openDialog(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row" link type="warning" @click="handleToggleStatus(scope.row)">
            {{ scope.row.status === 1 ? '禁用' : '启用' }}
          </el-button>
          <el-button v-if="scope?.row" link type="danger" @click="handleDelete(scope.row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 新建/编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑分类' : '新建分类'" width="480px" @close="resetForm">
      <el-form :model="form" label-width="90px">
        <el-form-item label="分类名称" required>
          <el-input v-model="form.name" placeholder="请输入分类名称" maxlength="50" />
        </el-form-item>
        <el-form-item label="父分类">
          <el-select v-model="form.parent_id" placeholder="选择父分类" style="width: 100%" :disabled="isEdit">
            <el-option v-for="opt in parentOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="图标 URL">
          <el-input v-model="form.icon" placeholder="请输入图标 URL" maxlength="200" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="999" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeDialog">取消</el-button>
        <el-button type="primary" @click="submitForm">保存</el-button>
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
.filter-bar {
  display: flex;
  gap: 12px;
}
.category-icon {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  object-fit: cover;
}
</style>
