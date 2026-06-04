<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { getAdminCoupons, createAdminCoupon, updateAdminCoupon, toggleCouponStatus } from '@/api/admin'
import { ElMessage, ElMessageBox } from 'element-plus'

const coupons = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(10)
const loading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const form = reactive<any>({ name: '', type: 1, value: 0, min_amount: 0, total_count: 100, start_at: '', end_at: '' })

function resetForm() { form.name = ''; form.type = 1; form.value = 0; form.min_amount = 0; form.total_count = 100; form.start_at = ''; form.end_at = '' }
function openCreate() { resetForm(); isEdit.value = false; dialogVisible.value = true }
function openEdit(row: any) { Object.assign(form, row); isEdit.value = true; dialogVisible.value = true }

async function handleSave() {
  try {
    if (isEdit.value && form.id) { await updateAdminCoupon(form.id, form); ElMessage.success('更新成功') }
    else { await createAdminCoupon(form); ElMessage.success('创建成功') }
    dialogVisible.value = false; loadCoupons()
  } catch (e) { console.error(e) }
}

async function handleToggle(row: any) {
  try {
    await ElMessageBox.confirm(row.status === 1 ? '确认下架？' : '确认上架？', '提示', { type: 'warning' })
    await toggleCouponStatus(row.id); ElMessage.success('操作成功'); loadCoupons()
  } catch { }
}

async function loadCoupons() {
  loading.value = true
  try { const res = await getAdminCoupons({ page: currentPage.value, per_page: pageSize.value }); coupons.value = res.data; total.value = res.total }
  catch (e) { console.error(e) } finally { loading.value = false }
}
onMounted(loadCoupons)
</script>

<template>
  <div class="coupon-management">
    <div class="page-header"><h3>优惠券管理</h3><el-button type="primary" @click="openCreate">新建</el-button></div>
    <el-table :data="coupons" v-loading="loading" stripe>
      <el-table-column prop="name" label="名称" />
      <el-table-column prop="type" label="类型" />
      <el-table-column prop="value" label="面值" />
      <el-table-column prop="claimed_count" label="已领/总量" />
      <el-table-column prop="status" label="状态" />
      <el-table-column label="操作">
        <template #default="scope">
          <el-button v-if="scope?.row" link type="primary" @click="openEdit(scope.row)">编辑</el-button>
          <el-button v-if="scope?.row" link :type="scope.row.status === 1 ? 'danger' : 'success'" @click="handleToggle(scope.row)">{{ scope.row.status === 1 ? '下架' : '上架' }}</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :total="total" layout="total, prev, pager, next" class="pagination" @change="loadCoupons" />
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑' : '新建'" width="560px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="类型"><el-radio-group v-model="form.type"><el-radio :label="1">满减</el-radio><el-radio :label="2">折扣</el-radio><el-radio :label="3">直减</el-radio></el-radio-group></el-form-item>
        <el-form-item label="面值"><el-input-number v-model="form.value" :min="1" /></el-form-item>
        <el-form-item label="最低消费"><el-input-number v-model="form.min_amount" :min="0" /></el-form-item>
        <el-form-item label="总量"><el-input-number v-model="form.total_count" :min="1" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible = false">取消</el-button><el-button type="primary" @click="handleSave">保存</el-button></template>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h3 { font-size: 18px; font-weight: 600; margin: 0; }
.pagination { margin-top: 20px; justify-content: flex-end; }
</style>
