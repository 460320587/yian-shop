<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
import { recharge, withdraw, getWalletTransactions, type WalletTransaction } from '@/api/wallet'

const userStore = useUserStore()

const balance = computed(() => userStore.userInfo?.balance ?? 0)

const rechargeVisible = ref(false)
const withdrawVisible = ref(false)
const submitting = ref(false)

const gatewayOptions = [
  { value: 'wechat', label: '微信支付' },
  { value: 'alipay', label: '支付宝' },
  { value: 'unionpay', label: '银联支付' },
]

const rechargeForm = ref({
  amount: 100,
  gateway: 'wechat',
})

const withdrawForm = ref({
  amount: 0,
  pay_password: '',
})

const activeTab = ref('overview')
const transactions = ref<WalletTransaction[]>([])
const transactionLoading = ref(false)
const transactionType = ref<number | undefined>(undefined)

const typeMap: Record<number, string> = {
  1: '充值',
  2: '消费',
  3: '提现',
  4: '退款',
}

function formatAmount(amount: number): string {
  return (amount / 100).toFixed(2)
}

async function loadTransactions() {
  transactionLoading.value = true
  try {
    const res = await getWalletTransactions({
      type: transactionType.value,
      page: 1,
      per_page: 20,
    })
    transactions.value = res.list || []
  } catch (e) {
    console.error(e)
  } finally {
    transactionLoading.value = false
  }
}

onMounted(() => {
  loadTransactions()
})

function openRechargeDialog() {
  rechargeForm.value = { amount: 100, gateway: 'wechat' }
  rechargeVisible.value = true
}

function openWithdrawDialog() {
  withdrawForm.value = { amount: 0, pay_password: '' }
  withdrawVisible.value = true
}

async function submitRecharge() {
  const amount = Number(rechargeForm.value.amount)
  if (!amount || amount < 1) {
    ElMessage.error('充值金额至少1元')
    return
  }
  if (amount > 50000) {
    ElMessage.error('单次充值最多50000元')
    return
  }

  submitting.value = true
  try {
    await recharge({
      amount,
      gateway: rechargeForm.value.gateway,
    })
    ElMessage.success('充值成功')
    rechargeVisible.value = false
  } catch (e) {
    console.error(e)
  } finally {
    submitting.value = false
  }
}

async function submitWithdraw() {
  const amount = Number(withdrawForm.value.amount)
  if (!amount || amount < 0.01) {
    ElMessage.error('提现金额至少0.01元')
    return
  }
  if (amount > 50000) {
    ElMessage.error('单次提现最多50000元')
    return
  }
  if (amount > balance.value) {
    ElMessage.error('余额不足')
    return
  }

  submitting.value = true
  try {
    await withdraw({ amount, pay_password: withdrawForm.value.pay_password })
    ElMessage.success('提现成功')
    loadTransactions()
    withdrawVisible.value = false
  } catch (e) {
    console.error(e)
  } finally {
    submitting.value = false
  }
}

defineExpose({
  balance,
  rechargeVisible,
  withdrawVisible,
  rechargeForm,
  withdrawForm,
  activeTab,
  transactions,
  transactionType,
  loadTransactions,
  openRechargeDialog,
  openWithdrawDialog,
  submitRecharge,
  submitWithdraw,
})
</script>

<template>
  <div class="wallet-view page-container">
    <h2 class="page-title">我的钱包</h2>

    <div class="wallet-card">
      <div class="balance-label">账户余额（元）</div>
      <div class="balance-amount">¥{{ balance.toFixed(2) }}</div>
      <div class="balance-actions">
        <el-button type="primary" @click="openRechargeDialog">充值</el-button>
        <el-button @click="openWithdrawDialog">提现</el-button>
      </div>
    </div>

    <el-dialog v-model="rechargeVisible" title="余额充值" width="400px">
      <el-form label-width="90px">
        <el-form-item label="充值金额" required>
          <el-input-number v-model="rechargeForm.amount" :min="1" :max="50000" :precision="2" />
        </el-form-item>
        <el-form-item label="支付方式" required>
          <el-radio-group v-model="rechargeForm.gateway">
            <el-radio-button v-for="opt in gatewayOptions" :key="opt.value" :label="opt.value">
              {{ opt.label }}
            </el-radio-button>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rechargeVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitRecharge">确认充值</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="withdrawVisible" title="余额提现" width="400px">
      <el-form label-width="90px">
        <el-form-item label="提现金额" required>
          <el-input-number v-model="withdrawForm.amount" :min="0.01" :max="balance" :precision="2" />
        </el-form-item>
        <el-form-item>
          <span class="tip">可提现金额：¥{{ balance.toFixed(2) }}</span>
        </el-form-item>
        <el-form-item label="支付密码" required class="withdraw-pay-password">
          <el-input
            v-model="withdrawForm.pay_password"
            type="password"
            placeholder="请输入支付密码"
            show-password
            maxlength="20"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="withdrawVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitWithdraw">确认提现</el-button>
      </template>
    </el-dialog>

    <div class="transaction-tab">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="交易明细" name="transactions">
          <div class="transaction-filter">
            <el-select v-model="transactionType" clearable placeholder="全部类型" @change="loadTransactions">
              <el-option label="充值" :value="1" />
              <el-option label="消费" :value="2" />
              <el-option label="提现" :value="3" />
              <el-option label="退款" :value="4" />
            </el-select>
          </div>
          <div v-if="transactionLoading" class="transaction-loading">加载中...</div>
          <div v-else class="transaction-list">
            <div v-for="tx in transactions" :key="tx.id" class="transaction-row">
              <span class="tx-time">{{ tx.created_at }}</span>
              <span class="tx-type">{{ typeMap[tx.type] || '其他' }}</span>
              <span :class="['tx-amount', tx.amount > 0 ? 'amount-positive' : 'amount-negative']">
                {{ tx.amount > 0 ? '+' : '' }}{{ formatAmount(tx.amount) }}
              </span>
              <span class="tx-balance">{{ formatAmount(tx.balance_after) }}</span>
              <span class="tx-remark">{{ tx.remark || '-' }}</span>
            </div>
            <div v-if="transactions.length === 0" class="transaction-empty">暂无交易记录</div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<style scoped>
.wallet-view {
  max-width: 560px;
  margin: 20px auto;
  padding: 20px;
}
.wallet-card {
  background: linear-gradient(135deg, #ff6b6b 0%, #f06595 100%);
  border-radius: 16px;
  padding: 40px 32px;
  color: #fff;
  text-align: center;
}
.balance-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 8px;
}
.balance-amount {
  font-size: 42px;
  font-weight: 700;
  margin-bottom: 24px;
}
.balance-actions {
  display: flex;
  justify-content: center;
  gap: 16px;
}
.balance-actions .el-button {
  min-width: 100px;
}
.tip {
  font-size: 13px;
  color: #909399;
}
.transaction-tab {
  margin-top: 24px;
  background: #fff;
  border-radius: 8px;
  padding: 16px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.transaction-filter {
  margin-bottom: 12px;
}
.amount-positive {
  color: #67c23a;
}
.amount-negative {
  color: #f56c6c;
}
.transaction-list {
  border: 1px solid #ebeef5;
  border-radius: 4px;
}
.transaction-row {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #ebeef5;
  font-size: 14px;
}
.transaction-row:last-child {
  border-bottom: none;
}
.transaction-row > span {
  flex: 1;
}
.transaction-row .tx-time {
  flex: 1.4;
  color: #606266;
}
.transaction-row .tx-type {
  flex: 0.6;
  color: #606266;
}
.transaction-row .tx-amount {
  flex: 0.8;
  font-weight: 600;
}
.transaction-row .tx-balance {
  flex: 0.8;
  color: #909399;
}
.transaction-row .tx-remark {
  flex: 1.2;
  color: #909399;
}
.transaction-empty,
.transaction-loading {
  padding: 40px;
  text-align: center;
  color: #909399;
  font-size: 14px;
}
</style>
