<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { getAdminProducts, createAdminProduct, updateAdminProduct, getAdminProductDetail, toggleProductStatus } from '@/api/admin'
import { ElMessage } from 'element-plus'

const products = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const keyword = ref('')
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const form = reactive({ name: '', code: '', category_id: 1, price_min: 0, price_max: 0, status: 1 })

async function loadProducts() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, per_page: pageSize.value }
    if (keyword.value) params.keyword = keyword.value
    const res = await getAdminProducts(params)
    products.value = res.data
    total.value = res.total
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function onSearch() { currentPage.value = 1; loadProducts() }

function openCreate() { isEdit.value = false; editId.value = null; form.name = ''; form.code = ''; form.price_min = 0; form.price_max = 0; dialogVisible.value = true }

async function openEdit(row: any) {
  isEdit.value = true
  editId.value = row.id
  try {
    const res = await getAdminProductDetail(row.id)
    form.name = res.name
    form.code = res.code
    form.category_id = res.category?.id ?? 1
    form.price_min = res.price_min
    form.price_max = res.price_max
    form.status = res.status
    dialogVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载商品详情失败')
  }
}

async function handleSave() {
  try {
    if (isEdit.value && editId.value) {
      await updateAdminProduct(editId.value, { name: form.name, code: form.code, category_id: form.category_id, price_min: form.price_min, price_max: form.price_max, status: form.status })
      ElMessage.success('更新成功')
    } else {
      await createAdminProduct(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadProducts()
  } catch (e) { console.error(e) }
}

async function handleToggle(row: any) {
  try { await toggleProductStatus(row.id); ElMessage.success('操作成功'); loadProducts() }
  catch (e) { console.error(e) }
}

onMounted(loadProducts)
</script>

<template>
  <div class="product-management">
    <div class="page-header">
      <h3>商品管理</h3>
      <div class="filter-bar">
        <el-input v-model="keyword" placeholder="搜索商品名称/编码" clearable style="width: 260px" @keyup.enter="onSearch" />
        <el-button type="primary" @click="onSearch">查询</el-button>
        <el-button type="primary" @click="openCreate">新建商品</el-button>
      </div>
    </div>
    <el-table :data="products" v-loading="loading" stripe>
      <el-table-column prop="name" label="名称" />
      <el-table-column prop="code" label="编码" />
      <el-table-column prop="price_min" label="最低价" />
      <el-table-column prop="price_max" label="最高价" />
      <el-table-column prop="status" label="状态">
        <template #default="scope"><el-tag v-if="scope?.row" :type="scope.row.status === 1 ? 'success' : 'info'">{{ scope.row.status === 1 ? '上架' : '下架' }}</el-tag></template>
      </el-table-column>
      <el-table-column label="操作">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openEdit(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row" link :type="scope.row.status === 1 ? 'danger' : 'success'" @click="handleToggle(scope.row)">{{ scope.row.status === 1 ? '下架' : '上架' }}</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadProducts" />
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑商品' : '新建商品'" width="500px">
      <el-form :model="form" label-width="90px">
        <el-form-item label="名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="编码"><el-input v-model="form.code" /></el-form-item>
        <el-form-item label="最低价"><el-input-number v-model="form.price_min" :min="0" /></el-form-item>
        <el-form-item label="最高价"><el-input-number v-model="form.price_max" :min="0" /></el-form-item>
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
