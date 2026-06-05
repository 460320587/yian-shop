<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { createPayment, getPaymentStatus, mockPaymentCallback, type PaymentData } from '@/api/payment'

const route = useRoute()
const router = useRouter()

const orderNo = ref('')
const amount = ref(0)
const selectedGateway = ref('wechat')
const loading = ref(false)
const errorMsg = ref('')
const paymentData = ref<PaymentData | null>(null)

const gateways = [
  { key: 'wechat', label: '微信支付', icon: 'Wechat' },
  { key: 'alipay', label: '支付宝', icon: 'AliPay' },
  { key: 'unionpay', label: '银联支付', icon: 'UnionPay' },
  { key: 'wallet', label: '余额支付', icon: 'Wallet' },
]

function init() {
  const ono = route.query.orderNo as string
  const amt = Number(route.query.amount)

  if (!ono || !amt || isNaN(amt)) {
    errorMsg.value = '参数缺失，请从订单页面进入支付'
    return
  }

  orderNo.value = ono
  amount.value = amt / 100
}

async function createPay() {
  if (!orderNo.value) return

  loading.value = true
  try {
    const res = await createPayment({
      order_no: orderNo.value,
      gateway: selectedGateway.value,
    })
    paymentData.value = res
    ElMessage.success('支付单创建成功')

    if (res.status === 1) {
      ElMessage.success('支付成功')
      router.push('/orders')
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function mockPaySuccess() {
  if (!paymentData.value) return

  loading.value = true
  try {
    await mockPaymentCallback(paymentData.value.payment_id, { status: 'success' })
    ElMessage.success('支付成功')
    router.push('/orders')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function checkStatus() {
  if (!paymentData.value) return

  try {
    const res = await getPaymentStatus(paymentData.value.payment_id)
    paymentData.value.status = res.status
    if (res.status === 1) {
      ElMessage.success('支付成功')
      router.push('/orders')
    } else {
      ElMessage.info('等待支付中')
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  init()
})

defineExpose({
  orderNo,
  amount,
  selectedGateway,
  errorMsg,
  paymentData,
  createPayment: createPay,
  mockPaySuccess,
  checkStatus,
})
</script>

<template>
  <div class="payment-view page-container">
    <h2 class="page-title">订单支付</h2>

    <div v-if="errorMsg" class="error-box">
      {{ errorMsg }}
    </div>

    <div v-else class="payment-panel">
      <div class="order-info">
        <div class="info-row">
          <span class="label">订单编号：</span>
          <span class="value">{{ orderNo }}</span>
        </div>
        <div class="info-row">
          <span class="label">支付金额：</span>
          <span class="amount">¥{{ amount.toFixed(2) }}</span>
        </div>
      </div>

      <div v-if="!paymentData" class="gateway-section">
        <h3>选择支付方式</h3>
        <div class="gateway-list">
          <div
            v-for="gw in gateways"
            :key="gw.key"
            :class="['gateway-item', { active: selectedGateway === gw.key }]"
            @click="selectedGateway = gw.key"
          >
            <span class="gateway-label">{{ gw.label }}</span>
            <el-icon v-if="selectedGateway === gw.key"><Check /></el-icon>
          </div>
        </div>
        <el-button type="primary" class="pay-btn" :loading="loading" @click="createPay">
          确认支付 ¥{{ amount.toFixed(2) }}
        </el-button>
      </div>

      <div v-else class="payment-result">
        <div v-if="paymentData.status === 0" class="pending-state">
          <p class="pay-tip">请使用 {{ gateways.find(g => g.key === paymentData?.gateway)?.label }} 完成支付</p>
          <div v-if="paymentData.credential?.qrcode_url" class="qrcode-box">
            <img :src="paymentData.credential.qrcode_url" alt="支付二维码" />
          </div>
          <div class="mock-actions">
            <el-button type="success" @click="mockPaySuccess">模拟支付成功</el-button>
            <el-button @click="checkStatus">查询支付状态</el-button>
          </div>
        </div>
        <div v-else-if="paymentData.status === 1" class="success-state">
          <p>支付成功！</p>
          <el-button type="primary" @click="$router.push('/orders')">查看订单</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.payment-view {
  max-width: 560px;
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
.payment-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.order-info {
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
  margin-bottom: 20px;
}
.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 15px;
}
.amount {
  color: #f56c6c;
  font-size: 20px;
  font-weight: 700;
}
.gateway-section h3 {
  margin-bottom: 12px;
  font-size: 16px;
}
.gateway-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}
.gateway-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}
.gateway-item:hover {
  border-color: #409eff;
}
.gateway-item.active {
  border-color: #409eff;
  background: #ecf5ff;
}
.pay-btn {
  width: 100%;
  height: 46px;
  font-size: 16px;
}
.payment-result {
  text-align: center;
  padding: 20px 0;
}
.pending-state .pay-tip {
  margin-bottom: 16px;
  color: #606266;
}
.qrcode-box {
  margin-bottom: 20px;
}
.qrcode-box img {
  width: 200px;
  height: 200px;
  border: 1px solid #ebeef5;
  border-radius: 4px;
}
.mock-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
}
.success-state {
  color: #67c23a;
  font-size: 18px;
  font-weight: 600;
}
</style>
