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

const pricingForm = reactive({
  base_price: 0,
  unit: '',
  price_tiers: [] as { min_qty: number; price: number }[],
  paper_options: [] as { id: number; name: string; price_factor: number }[],
  color_options: [] as { id: number; name: string; price_factor: number }[],
  process_options: [] as { id: number; name: string; price: number; unit: string }[],
})

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

function openCreate() {
  isEdit.value = false; editId.value = null
  form.name = ''; form.code = ''; form.price_min = 0; form.price_max = 0
  resetPricingForm()
  dialogVisible.value = true
}

function resetPricingForm() {
  pricingForm.base_price = 0; pricingForm.unit = ''
  pricingForm.price_tiers = []; pricingForm.paper_options = []
  pricingForm.color_options = []; pricingForm.process_options = []
}

function loadPricingForm(data: any) {
  if (!data) { resetPricingForm(); return }
  pricingForm.base_price = data.base_price ?? 0
  pricingForm.unit = data.unit ?? ''
  pricingForm.price_tiers = (data.price_tiers ?? []).map((t: any) => ({ min_qty: t.min_qty, price: t.price }))
  pricingForm.paper_options = (data.paper_options ?? []).map((o: any) => ({ id: o.id, name: o.name, price_factor: o.price_factor }))
  pricingForm.color_options = (data.color_options ?? []).map((o: any) => ({ id: o.id, name: o.name, price_factor: o.price_factor }))
  pricingForm.process_options = (data.process_options ?? []).map((o: any) => ({ id: o.id, name: o.name, price: o.price, unit: o.unit }))
}

function buildPricingParams(): any {
  const hasData = pricingForm.base_price > 0 || pricingForm.unit || pricingForm.price_tiers.length > 0 || pricingForm.paper_options.length > 0 || pricingForm.color_options.length > 0 || pricingForm.process_options.length > 0
  if (!hasData) return null
  return {
    base_price: pricingForm.base_price,
    unit: pricingForm.unit,
    price_tiers: pricingForm.price_tiers,
    paper_options: pricingForm.paper_options,
    color_options: pricingForm.color_options,
    process_options: pricingForm.process_options,
  }

}

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
    loadPricingForm(res.pricing_params)
    dialogVisible.value = true
  } catch (e) {
    console.error(e)
    ElMessage.error('加载商品详情失败')
  }
}

async function handleSave() {
  try {
    if (isEdit.value && editId.value) {
      await updateAdminProduct(editId.value, {
        name: form.name, code: form.code, category_id: form.category_id,
        price_min: form.price_min, price_max: form.price_max, status: form.status,
        pricing_params: buildPricingParams(),
      })
      ElMessage.success('更新成功')
    } else {
      await createAdminProduct({ ...form, pricing_params: buildPricingParams() })
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

function addPriceTier() { pricingForm.price_tiers.push({ min_qty: 100, price: 0 }) }
function removePriceTier(idx: number) { pricingForm.price_tiers.splice(idx, 1) }

function addPaperOption() { pricingForm.paper_options.push({ id: Date.now(), name: '', price_factor: 1 }) }
function removePaperOption(idx: number) { pricingForm.paper_options.splice(idx, 1) }

function addColorOption() { pricingForm.color_options.push({ id: Date.now(), name: '', price_factor: 1 }) }
function removeColorOption(idx: number) { pricingForm.color_options.splice(idx, 1) }

function addProcessOption() { pricingForm.process_options.push({ id: Date.now(), name: '', price: 0, unit: '' }) }
function removeProcessOption(idx: number) { pricingForm.process_options.splice(idx, 1) }

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

    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑商品' : '新建商品'" width="640px">
      <el-form :model="form" label-width="90px">
        <el-form-item label="名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="编码"><el-input v-model="form.code" :disabled="isEdit" /></el-form-item>
        <el-form-item label="最低价"><el-input-number v-model="form.price_min" :min="0" /></el-form-item>
        <el-form-item label="最高价"><el-input-number v-model="form.price_max" :min="0" /></el-form-item>
      </el-form>

      <el-divider content-position="left">定价参数</el-divider>
      <el-form :model="pricingForm" label-width="100px">
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="基础价格"><el-input-number v-model="pricingForm.base_price" :min="0" style="width: 100%" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="单位"><el-input v-model="pricingForm.unit" placeholder="如：本/张/㎡" /></el-form-item></el-col>
        </el-row>

        <div class="pricing-section">
          <div class="section-header"><span>价格阶梯</span><el-button link type="primary" @click="addPriceTier">+ 添加</el-button></div>
          <div v-for="(tier, idx) in pricingForm.price_tiers" :key="idx" class="pricing-row">
            <el-input-number v-model="tier.min_qty" :min="1" placeholder="起订量" />
            <el-input-number v-model="tier.price" :min="0" placeholder="单价" />
            <el-button link type="danger" @click="removePriceTier(idx)">删除</el-button>
          </div>
        </div>

        <div class="pricing-section">
          <div class="section-header"><span>纸张选项</span><el-button link type="primary" @click="addPaperOption">+ 添加</el-button></div>
          <div v-for="(opt, idx) in pricingForm.paper_options" :key="opt.id" class="pricing-row">
            <el-input v-model="opt.name" placeholder="名称" />
            <el-input-number v-model="opt.price_factor" :min="0" :precision="2" placeholder="价格系数" />
            <el-button link type="danger" @click="removePaperOption(idx)">删除</el-button>
          </div>
        </div>

        <div class="pricing-section">
          <div class="section-header"><span>颜色选项</span><el-button link type="primary" @click="addColorOption">+ 添加</el-button></div>
          <div v-for="(opt, idx) in pricingForm.color_options" :key="opt.id" class="pricing-row">
            <el-input v-model="opt.name" placeholder="名称" />
            <el-input-number v-model="opt.price_factor" :min="0" :precision="2" placeholder="价格系数" />
            <el-button link type="danger" @click="removeColorOption(idx)">删除</el-button>
          </div>
        </div>

        <div class="pricing-section">
          <div class="section-header"><span>工艺选项</span><el-button link type="primary" @click="addProcessOption">+ 添加</el-button></div>
          <div v-for="(opt, idx) in pricingForm.process_options" :key="opt.id" class="pricing-row">
            <el-input v-model="opt.name" placeholder="名称" />
            <el-input-number v-model="opt.price" :min="0" placeholder="价格" />
            <el-input v-model="opt.unit" placeholder="单位" style="width: 100px" />
            <el-button link type="danger" @click="removeProcessOption(idx)">删除</el-button>
          </div>
        </div>
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
.pricing-section { margin-bottom: 16px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: #606266; }
.pricing-row { display: flex; gap: 8px; align-items: center; margin-bottom: 6px; }
.pricing-row .el-input-number { flex: 1; }
.pricing-row .el-input { flex: 1; }
</style>
