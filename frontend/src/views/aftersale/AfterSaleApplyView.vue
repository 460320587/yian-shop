<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getOrderDetail } from '@/api/order'
import { createAfterSale } from '@/api/aftersale'

const route = useRoute()
const router = useRouter()

const orderId = ref(0)
const orderNo = ref('')
const orderItems = ref<any[]>([])
const loading = ref(false)
const errorMsg = ref('')

const selectedItems = ref<Record<number, boolean>>({})
const quantities = ref<Record<number, number>>({})

const typeOptions = [
  { value: 1, label: '退货' },
  { value: 2, label: '换货' },
  { value: 3, label: '退款' },
  { value: 4, label: '补发' },
  { value: 5, label: '其他' },
]

interface FormState {
  type: number
  reason: string
  description: string
  images: string[]
}

const form = ref<FormState>({
  type: 1,
  reason: '',
  description: '',
  images: [],
})

function init() {
  const oid = Number(route.query.orderId)
  const ono = route.query.orderNo as string

  if (!oid || !ono) {
    errorMsg.value = '参数缺失，请从订单页面进入售后申请'
    return
  }

  orderId.value = oid
  orderNo.value = ono
  loadOrderDetail()
}

async function loadOrderDetail() {
  loading.value = true
  try {
    const res = await getOrderDetail(orderId.value)
    orderItems.value = res.items || []
    res.items?.forEach((item: any) => {
      quantities.value[item.id] = item.quantity
    })
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function getSelectedItems(): { order_item_id: number; quantity: number }[] {
  const result: { order_item_id: number; quantity: number }[] = []
  for (const [itemId, checked] of Object.entries(selectedItems.value)) {
    if (checked) {
      const id = Number(itemId)
      const qty = quantities.value[id] || 1
      result.push({ order_item_id: id, quantity: qty })
    }
  }
  return result
}

async function validateForm(): Promise<boolean> {
  const items = getSelectedItems()
  if (items.length === 0) {
    ElMessage.error('请至少选择一件商品')
    return false
  }
  if (!form.value.reason.trim()) {
    ElMessage.error('请填写售后原因')
    return false
  }
  if (form.value.reason.length > 500) {
    ElMessage.error('售后原因最多500字')
    return false
  }
  if (form.value.description && form.value.description.length > 2000) {
    ElMessage.error('详细描述最多2000字')
    return false
  }
  return true
}

async function submitForm() {
  const valid = await validateForm()
  if (!valid) return

  loading.value = true
  try {
    await createAfterSale({
      order_no: orderNo.value,
      type: form.value.type,
      reason: form.value.reason.trim(),
      description: form.value.description.trim() || undefined,
      images: form.value.images.length > 0 ? form.value.images : undefined,
      items: getSelectedItems(),
    })
    ElMessage.success('售后申请已提交')
    router.push('/after-sales')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  init()
})

defineExpose({
  orderId,
  orderNo,
  orderItems,
  errorMsg,
  selectedItems,
  quantities,
  form,
  getSelectedItems,
  validateForm,
  submitForm,
  loadOrderDetail,
})
</script>

<template>
  <div class="after-sale-apply-view page-container">
    <h2 class="page-title">申请售后</h2>

    <div v-if="errorMsg" class="error-box">
      {{ errorMsg }}
    </div>

    <div v-else v-loading="loading" class="apply-panel">
      <div class="order-info">
        <span class="order-no">订单：{{ orderNo }}</span>
      </div>

      <div class="section">
        <h3>选择售后商品</h3>
        <div v-for="item in orderItems" :key="item.id" class="product-row">
          <el-checkbox v-model="selectedItems[item.id]">
            {{ item.product_name }}（单价 ¥{{ item.unit_price }}）
          </el-checkbox>
          <div v-if="selectedItems[item.id]" class="quantity-row">
            <span>数量：</span>
            <el-input-number
              v-model="quantities[item.id]"
              :min="1"
              :max="item.quantity"
              size="small"
            />
            <span class="max-qty">/ {{ item.quantity }}</span>
          </div>
        </div>
      </div>

      <div class="section">
        <h3>售后信息</h3>
        <el-form label-width="100px">
          <el-form-item label="售后类型">
            <el-radio-group v-model="form.type">
              <el-radio-button v-for="opt in typeOptions" :key="opt.value" :label="opt.value">
                {{ opt.label }}
              </el-radio-button>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="售后原因" required>
            <el-input
              v-model="form.reason"
              type="textarea"
              :rows="2"
              placeholder="请填写售后原因"
              maxlength="500"
              show-word-limit
            />
          </el-form-item>
          <el-form-item label="详细描述">
            <el-input
              v-model="form.description"
              type="textarea"
              :rows="3"
              placeholder="请补充详细描述（选填）"
              maxlength="2000"
              show-word-limit
            />
          </el-form-item>
        </el-form>
      </div>

      <div class="actions">
        <el-button type="primary" :loading="loading" @click="submitForm">提交申请</el-button>
        <el-button @click="$router.back()">取消</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.after-sale-apply-view {
  max-width: 680px;
  margin: 20px auto;
  padding: 20px;
}
.error-box {
  padding: 20px;
  text-align: center;
  color: #f56c6c;
  background: #fef0f0;
  border-radius: 4px;
}
.apply-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.order-info {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.order-no {
  font-weight: 600;
  font-size: 15px;
}
.section {
  margin-bottom: 24px;
}
.section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.product-row {
  padding: 12px;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  margin-bottom: 10px;
}
.quantity-row {
  margin-top: 8px;
  padding-left: 24px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.max-qty {
  color: #909399;
  font-size: 13px;
}
.actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
