<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getOrders } from '@/api/order'
import { getInvoiceTitles, createInvoiceApply } from '@/api/invoice'
import type { InvoiceTitle } from '@/api/invoice'

const router = useRouter()

const orders = ref<any[]>([])
const titles = ref<InvoiceTitle[]>([])
const loading = ref(false)
const submitting = ref(false)

const typeOptions = [
  { value: 1, label: '普通发票' },
  { value: 2, label: '专用发票' },
]

interface FormState {
  order_id: number | null
  title_id: number | null
  type: number
  email: string
  address: string
  remark: string
}

const form = ref<FormState>({
  order_id: null,
  title_id: null,
  type: 1,
  email: '',
  address: '',
  remark: '',
})

async function loadData() {
  loading.value = true
  try {
    const [ordersRes, titlesRes] = await Promise.all([
      getOrders({ page: 1 }),
      getInvoiceTitles(),
    ])
    orders.value = ordersRes.data
    titles.value = titlesRes
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function validateForm(): boolean {
  if (!form.value.order_id) {
    ElMessage.error('请选择订单')
    return false
  }
  if (!form.value.title_id) {
    ElMessage.error('请选择发票抬头')
    return false
  }
  if (form.value.email && form.value.email.length > 100) {
    ElMessage.error('邮箱最多100个字符')
    return false
  }
  if (form.value.address && form.value.address.length > 255) {
    ElMessage.error('地址最多255个字符')
    return false
  }
  if (form.value.remark && form.value.remark.length > 500) {
    ElMessage.error('备注最多500个字符')
    return false
  }
  return true
}

async function submitForm() {
  if (!validateForm()) return

  submitting.value = true
  try {
    await createInvoiceApply({
      order_id: form.value.order_id!,
      title_id: form.value.title_id!,
      type: form.value.type,
      email: form.value.email || undefined,
      address: form.value.address || undefined,
      remark: form.value.remark || undefined,
    })
    ElMessage.success('发票申请已提交')
    router.back()
  } catch (e) {
    console.error(e)
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(() => {
  loadData()
})

defineExpose({
  orders,
  titles,
  form,
  loading,
  submitting,
  loadData,
  submitForm,
  goBack,
})
</script>

<template>
  <div class="invoice-apply-view page-container">
    <h2 class="page-title">申请发票</h2>

    <div v-loading="loading" class="apply-panel">
      <el-form label-width="100px">
        <el-form-item label="选择订单" required>
          <el-select v-model="form.order_id" placeholder="请选择订单" class="form-select">
            <el-option
              v-for="order in orders"
              :key="order.id"
              :label="`${order.order_no} (¥${order.total_amount})`"
              :value="order.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="发票抬头" required>
          <el-select v-model="form.title_id" placeholder="请选择发票抬头" class="form-select">
            <el-option
              v-for="title in titles"
              :key="title.id"
              :label="title.company_name"
              :value="title.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="发票类型" required>
          <el-radio-group v-model="form.type">
            <el-radio-button v-for="opt in typeOptions" :key="opt.value" :label="opt.value">
              {{ opt.label }}
            </el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="接收邮箱">
          <el-input v-model="form.email" placeholder="请填写接收邮箱" maxlength="100" />
        </el-form-item>

        <el-form-item label="邮寄地址">
          <el-input v-model="form.address" placeholder="请填写邮寄地址（纸质发票）" maxlength="255" />
        </el-form-item>

        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" :rows="2" placeholder="请填写备注" maxlength="500" />
        </el-form-item>
      </el-form>

      <div class="actions">
        <el-button type="primary" :loading="submitting" @click="submitForm">提交申请</el-button>
        <el-button @click="goBack">取消</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.invoice-apply-view {
  max-width: 560px;
  margin: 20px auto;
  padding: 20px;
}
.apply-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.form-select {
  width: 100%;
}
.actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
