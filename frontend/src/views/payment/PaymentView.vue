<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
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
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null)
const countdownTimer = ref<ReturnType<typeof setInterval> | null>(null)
const countdownSeconds = ref(0)
const isExpired = ref(false)

const payPassword = ref('')
const isDev = import.meta.env.DEV

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

const countdownText = computed(() => {
  const m = Math.floor(countdownSeconds.value / 60)
  const s = countdownSeconds.value % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

function startCountdown(expireAt: string | null) {
  stopCountdown()
  if (!expireAt) {
    countdownSeconds.value = 30 * 60
  } else {
    const diff = Math.floor((new Date(expireAt).getTime() - Date.now()) / 1000)
    countdownSeconds.value = Math.max(0, diff)
  }

  countdownTimer.value = setInterval(() => {
    countdownSeconds.value--
    if (countdownSeconds.value <= 0) {
      isExpired.value = true
      stopCountdown()
      stopPolling()
    }
  }, 1000)
}

function stopCountdown() {
  if (countdownTimer.value) {
    clearInterval(countdownTimer.value)
    countdownTimer.value = null
  }
}

function startPolling(paymentId: number) {
  stopPolling()
  pollTimer.value = setInterval(async () => {
    if (isExpired.value || !paymentData.value) {
      stopPolling()
      return
    }
    try {
      const res = await getPaymentStatus(paymentId)
      if (res.status === 1) {
        paymentData.value.status = 1
        ElMessage.success('支付成功')
        stopPolling()
        stopCountdown()
        router.push('/orders')
      }
    } catch {
      // ignore polling errors
    }
  }, 3000)
}

function stopPolling() {
  if (pollTimer.value) {
    clearInterval(pollTimer.value)
    pollTimer.value = null
  }
}

async function createPay() {
  if (!orderNo.value) return

  loading.value = true
  try {
    const payload: { order_no: string; gateway: string; pay_password?: string } = {
      order_no: orderNo.value,
      gateway: selectedGateway.value,
    }
    if (selectedGateway.value === 'wallet') {
      payload.pay_password = payPassword.value
    }
    const res = await createPayment(payload)
    paymentData.value = res

    if (res.status === 1) {
      ElMessage.success('支付成功')
      router.push('/orders')
      return
    }

    ElMessage.success('支付单创建成功')
    startPolling(res.payment_id)
    startCountdown(res.expire_at)
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
    stopPolling()
    stopCountdown()
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
      stopPolling()
      stopCountdown()
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

onUnmounted(() => {
  stopPolling()
  stopCountdown()
})

defineExpose({
  orderNo,
  amount,
  selectedGateway,
  payPassword,
  errorMsg,
  paymentData,
  createPayment: createPay,
  mockPaySuccess,
  checkStatus,
  countdownText,
  isExpired,
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
        <div v-if="selectedGateway === 'wallet'" class="pay-password-input">
          <el-input
            v-model="payPassword"
            type="password"
            placeholder="请输入支付密码"
            show-password
            maxlength="20"
          />
        </div>
        <el-button type="primary" class="pay-btn" :loading="loading" @click="createPay">
          确认支付 ¥{{ amount.toFixed(2) }}
        </el-button>
      </div>

      <div v-else class="payment-result">
        <div v-if="paymentData.status === 0" class="pending-state">
          <p class="pay-tip">
            请使用 {{ gateways.find(g => g.key === paymentData?.gateway)?.label }} 完成支付
          </p>

          <div v-if="isExpired" class="expired-notice">
            <p>支付单已过期，请重新发起支付</p>
            <el-button type="primary" @click="paymentData = null">重新支付</el-button>
          </div>

          <div v-else>
            <div class="countdown-box">
              <span class="countdown-label">支付剩余时间</span>
              <span class="countdown-value">{{ countdownText }}</span>
            </div>

            <!-- 二维码 -->
            <div v-if="paymentData.credential?.qrcode_url" class="qrcode-box">
              <img :src="paymentData.credential.qrcode_url" alt="支付二维码" />
              <p class="qrcode-tip">请使用 {{ gateways.find(g => g.key === paymentData?.gateway)?.label }} 扫码</p>
            </div>

            <!-- H5 跳转 -->
            <div v-else-if="paymentData.credential?.redirect_url" class="redirect-box">
              <p>请在浏览器中完成支付</p>
              <a :href="paymentData.credential.redirect_url" target="_blank" class="redirect-link">
                <el-button type="primary">前往支付</el-button>
              </a>
            </div>

            <!-- 表单支付 -->
            <div v-else-if="paymentData.credential?.form_html" class="form-box">
              <div v-html="paymentData.credential.form_html" />
            </div>

            <!-- 纯等待（如余额支付异步） -->
            <div v-else class="waiting-box">
              <p>正在处理支付，请勿关闭页面...</p>
            </div>

            <div class="mock-actions">
              <el-button v-if="isDev" type="success" @click="mockPaySuccess">模拟支付成功</el-button>
              <el-button @click="checkStatus">查询支付状态</el-button>
            </div>
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
.pay-password-input {
  margin-bottom: 16px;
}
.pay-password-input :deep(.el-input__inner) {
  height: 44px;
  font-size: 15px;
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
.countdown-box {
  margin-bottom: 16px;
}
.countdown-label {
  color: #909399;
  font-size: 14px;
  margin-right: 8px;
}
.countdown-value {
  color: #f56c6c;
  font-size: 20px;
  font-weight: 700;
  font-family: monospace;
}
.expired-notice {
  color: #f56c6c;
  margin-bottom: 16px;
}
.expired-notice p {
  margin-bottom: 12px;
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
.qrcode-tip {
  margin-top: 8px;
  color: #909399;
  font-size: 13px;
}
.redirect-box {
  margin-bottom: 20px;
}
.redirect-box p {
  color: #606266;
  margin-bottom: 12px;
}
.redirect-link {
  text-decoration: none;
}
.form-box {
  margin-bottom: 20px;
}
.waiting-box {
  margin-bottom: 20px;
  color: #409eff;
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
