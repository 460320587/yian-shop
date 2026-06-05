<script setup lang="ts">
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
import { recharge, withdraw } from '@/api/wallet'

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
})

function openRechargeDialog() {
  rechargeForm.value = { amount: 100, gateway: 'wechat' }
  rechargeVisible.value = true
}

function openWithdrawDialog() {
  withdrawForm.value = { amount: 0 }
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
    await withdraw({ amount })
    ElMessage.success('提现成功')
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
      </el-form>
      <template #footer>
        <el-button @click="withdrawVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitWithdraw">确认提现</el-button>
      </template>
    </el-dialog>
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
</style>
