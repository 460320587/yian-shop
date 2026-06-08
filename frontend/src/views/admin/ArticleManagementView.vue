<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import {
  getAdminArticles, getAdminArticleDetail,
  createAdminArticle, updateAdminArticle, deleteAdminArticle, toggleAdminArticleStatus,
} from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const articles = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const typeFilter = ref('')
const statusFilter = ref('')
const keyword = ref('')

const typeOptions = [
  { label: '全部', value: '' },
  { label: '新闻', value: 1 },
  { label: '公告', value: 2 },
  { label: '帮助', value: 3 },
  { label: 'SEO', value: 4 },
]

const statusOptions = [
  { label: '全部', value: '' },
  { label: '草稿', value: 0 },
  { label: '已发布', value: 1 },
  { label: '已下架', value: 2 },
]

const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)

const form = reactive({
  title: '', slug: '', type: 1, content: '', summary: '', cover: '', author: '', sort: 0, status: 1,
})

async function loadList() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (typeFilter.value !== '') params.type = Number(typeFilter.value)
    if (statusFilter.value !== '') params.status = Number(statusFilter.value)
    if (keyword.value) params.keyword = keyword.value
    const res = await getAdminArticles(params)
    articles.value = res.data
    total.value = res.total
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function onSearch() { currentPage.value = 1; loadList() }

function openCreate() {
  isEdit.value = false; editId.value = null
  form.title = ''; form.slug = ''; form.type = 1; form.content = ''
  form.summary = ''; form.cover = ''; form.author = ''; form.sort = 0; form.status = 1
  dialogVisible.value = true
}

async function openEdit(row: any) {
  isEdit.value = true; editId.value = row.id
  try {
    const res = await getAdminArticleDetail(row.id)
    form.title = res.title
    form.slug = res.slug
    form.type = res.type
    form.content = res.content
    form.summary = res.summary || ''
    form.cover = res.cover || ''
    form.author = res.author || ''
    form.sort = res.sort
    form.status = res.status
    dialogVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载文章详情失败')
  }
}

async function handleSave() {
  try {
    const data: any = {
      title: form.title,
      slug: form.slug,
      type: form.type,
      content: form.content,
      summary: form.summary || undefined,
      cover: form.cover || undefined,
      author: form.author || undefined,
      sort: form.sort,
      status: form.status,
    }
    if (isEdit.value && editId.value) {
      await updateAdminArticle(editId.value, data)
      ElMessage.success('更新成功')
    } else {
      await createAdminArticle(data)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadList()
  } catch (e) { console.error(e) }
}

async function handleToggle(row: any) {
  try { await toggleAdminArticleStatus(row.id); ElMessage.success('操作成功'); loadList() }
  catch (e) { console.error(e) }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定删除该文章吗？', '提示', { type: 'warning' })
    await deleteAdminArticle(row.id)
    ElMessage.success('删除成功')
    loadList()
  } catch (e) { /* cancelled */ }
}

function typeText(type: number): string {
  const map: Record<number, string> = { 1: '新闻', 2: '公告', 3: '帮助', 4: 'SEO' }
  return map[type] ?? '未知'
}

function statusText(status: number): string {
  const map: Record<number, string> = { 0: '草稿', 1: '已发布', 2: '已下架' }
  return map[status] ?? '未知'
}

function statusType(status: number): string {
  const map: Record<number, string> = { 0: 'info', 1: 'success', 2: 'danger' }
  return map[status] ?? ''
}

onMounted(loadList)
</script>

<template>
  <div class="article-management">
    <div class="page-header">
      <h3>文章管理</h3>
      <div class="filter-bar">
        <el-select v-model="typeFilter" placeholder="类型" clearable style="width: 120px" @change="onSearch">
          <el-option v-for="opt in typeOptions" :key="String(opt.value)" :label="opt.label" :value="opt.value" />
        </el-select>
        <el-select v-model="statusFilter" placeholder="状态" clearable style="width: 120px" @change="onSearch">
          <el-option v-for="opt in statusOptions" :key="String(opt.value)" :label="opt.label" :value="opt.value" />
        </el-select>
        <el-input v-model="keyword" placeholder="搜索标题" clearable style="width: 220px" @keyup.enter="onSearch" />
        <el-button type="primary" @click="onSearch">查询</el-button>
        <el-button type="primary" @click="openCreate">新建文章</el-button>
      </div>
    </div>
    <el-table :data="articles" v-loading="loading" stripe>
      <el-table-column prop="title" label="标题" min-width="200" />
      <el-table-column label="类型" width="100">
        <template #default="scope">
          <el-tag v-if="scope?.row" size="small">{{ typeText(scope.row.type) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="author" label="作者" width="120" />
      <el-table-column prop="view_count" label="浏览量" width="90" />
      <el-table-column prop="sort" label="排序" width="80" />
      <el-table-column label="状态" width="100">
        <template #default="scope">
          <el-tag v-if="scope?.row" :type="statusType(scope.row.status)">{{ statusText(scope.row.status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" min-width="160" />
      <el-table-column label="操作" width="220">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openEdit(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row" link :type="scope.row.status === 1 ? 'danger' : 'success'" @click="handleToggle(scope.row)">{{ scope.row.status === 1 ? '下架' : '发布' }}</el-button>
          <el-button v-if="scope?.row" link type="danger" @click="handleDelete(scope.row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadList" />

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑文章' : '新建文章'" width="680px">
      <el-form :model="form" label-width="80px">
        <el-form-item label="标题"><el-input v-model="form.title" /></el-form-item>
        <el-form-item label="Slug"><el-input v-model="form.slug" :disabled="isEdit" /></el-form-item>
        <el-form-item label="类型">
          <el-select v-model="form.type" style="width: 100%">
            <el-option v-for="opt in typeOptions.slice(1)" :key="opt.value" :label="opt.label" :value="opt.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="作者"><el-input v-model="form.author" /></el-form-item>
        <el-form-item label="封面"><el-input v-model="form.cover" placeholder="图片URL" /></el-form-item>
        <el-form-item label="摘要"><el-input v-model="form.summary" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="内容"><el-input v-model="form.content" type="textarea" :rows="8" /></el-form-item>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" style="width: 100%" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="状态">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="opt in statusOptions.slice(1)" :key="opt.value" :label="opt.label" :value="opt.value" />
            </el-select>
          </el-form-item></el-col>
        </el-row>
      </el-form>
      <template #footer><el-button @click="dialogVisible = false">取消</el-button><el-button type="primary" @click="handleSave">保存</el-button></template>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
