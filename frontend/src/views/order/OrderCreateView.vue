<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { createOrder, type CreateOrderData } from '@/api/order'
import { getCart, type CartItem } from '@/api/cart'
import { getAddresses, type Address } from '@/api/address'
import { getMyCoupons, type CustomerCoupon } from '@/api/coupon'
import { getInvoiceTitles, type InvoiceTitle } from '@/api/invoice'
import { getUserInfo } from '@/api/user'

const router = useRouter()

const loading = ref(false)
const submitting = ref(false)

const cartItems = ref<CartItem[]>([])
const addresses = ref<Address[]>([])
const coupons = ref<CustomerCoupon[]>([])
const invoiceTitles = ref<InvoiceTitle[]>([])
const userInfo = ref<{ balance: number; points?: number } | null>(null)

const selectedAddressId = ref<number | null>(null)
const selectedCouponCode = ref<string | null>(null)
const invoiceType = ref(0)
const invoiceTitleInput = ref('')
const invoiceTaxNoInput = ref('')
const invoiceEmailInput = ref('')
const remark = ref('')
const useBalance = ref(0)

const selectedItems = computed(() => cartItems.value.filter((i) => i.selected))
const selectedAddress = computed(() =>
  addresses.value.find((a) => a.id === selectedAddressId.value) || null
)
const selectedCoupon = computed(() =>
  coupons.value.find((c) => c.code === selectedCouponCode.value) || null
)

const subtotal = computed(() =>
  selectedItems.value.reduce((sum, i) => sum + i.subtotal, 0)
)

const discount = computed(() => {
  const coupon = selectedCoupon.value?.coupon
  if (!coupon) return 0
  if (subtotal.value < coupon.min_amount) return 0
  if (coupon.type === 1) {
    return coupon.value
  }
  const pct = Math.floor((subtotal.value * coupon.value) / 100)
  return coupon.max_discount ? Math.min(pct, coupon.max_discount) : pct
})

const freight = computed(() => 0)
const total = computed(() =>
  Math.max(0, subtotal.value + freight.value - discount.value - useBalance.value)
)
const canSubmit = computed(
  () =>
    selectedAddressId.value !== null &&
    selectedItems.value.length > 0 &&
    !submitting.value
)

function formatPrice(price: number): string {
  return (price / 100).toFixed(2)
}

function selectAddress(id: number) {
  selectedAddressId.value = id
}

function selectInvoiceTitle(title: InvoiceTitle) {
  invoiceTitleInput.value = title.company_name
  invoiceTaxNoInput.value = title.tax_number || ''
}

function onInvoiceTypeChange() {
  if (invoiceType.value === 0) {
    invoiceTitleInput.value = ''
    invoiceTaxNoInput.value = ''
    invoiceEmailInput.value = ''
  }
}

async function loadData() {
  loading.value = true
  try {
    const [cartRes, addrRes, couponRes, invRes, userRes] = await Promise.all([
      getCart(),
      getAddresses({ page: 1, per_page: 50 }),
      getMyCoupons(1),
      getInvoiceTitles(),
      getUserInfo(),
    ])
    cartItems.value = cartRes.items || []
    addresses.value = addrRes.data || []
    coupons.value = couponRes.data || []
    invoiceTitles.value = invRes || []
    userInfo.value = userRes

    const defaultAddr = addresses.value.find((a) => a.is_default)
    if (defaultAddr) {
      selectedAddressId.value = defaultAddr.id
    } else if (addresses.value.length > 0) {
      selectedAddressId.value = addresses.value[0].id
    }
  } catch (e) {
    console.error(e)
    ElMessage.error('加载结算信息失败')
  } finally {
    loading.value = false
  }
}

async function submitOrder() {
  if (!canSubmit.value) {
    if (!selectedAddressId.value) {
      ElMessage.warning('请选择收货地址')
    }
    return
  }

  if (invoiceType.value !== 0) {
    if (!invoiceTitleInput.value.trim()) {
      ElMessage.warning('请填写发票抬头')
      return
    }
    if (!invoiceEmailInput.value.trim()) {
      ElMessage.warning('请填写发票接收邮箱')
      return
    }
    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRe.test(invoiceEmailInput.value)) {
      ElMessage.warning('邮箱格式不正确')
      return
    }
  }

  submitting.value = true
  try {
    const payload: CreateOrderData = {
      address_id: selectedAddressId.value!,
      cart_item_ids: selectedItems.value.map((i) => i.id),
      coupon_code: selectedCouponCode.value || undefined,
      invoice_type: invoiceType.value || undefined,
      invoice_title: invoiceType.value !== 0 ? invoiceTitleInput.value : undefined,
      invoice_tax_no: invoiceType.value === 2 ? invoiceTaxNoInput.value : undefined,
      invoice_email: invoiceType.value !== 0 ? invoiceEmailInput.value : undefined,
      remark: remark.value || undefined,
      use_balance: useBalance.value || undefined,
    }
    const res = await createOrder(payload)
    ElMessage.success('订单创建成功')
    router.push('/payment?orderNo=' + res.order_no + '&amount=' + res.total_amount)
  } catch (e: any) {
    const msg = e?.response?.data?.message || e?.message || '订单创建失败'
    ElMessage.error(msg)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadData()
})

defineExpose({
  loading,
  submitting,
  cartItems,
  addresses,
  coupons,
  invoiceTitles,
  userInfo,
  selectedAddressId,
  selectedCouponCode,
  invoiceType,
  invoiceTitleInput,
  invoiceTaxNoInput,
  invoiceEmailInput,
  remark,
  useBalance,
  selectedItems,
  selectedAddress,
  selectedCoupon,
  subtotal,
  discount,
  freight,
  total,
  canSubmit,
  formatPrice,
  selectAddress,
  selectInvoiceTitle,
  onInvoiceTypeChange,
  loadData,
  submitOrder,
})
</script>

<template>
  <div class="order-create-view page-container">
    <h2 class="page-title">确认订单</h2>
    <div v-loading="loading" class="checkout-grid">
      <!-- 左列：收货信息 + 发票 + 备注 -->
      <div class="col-left">
        <!-- 收货地址 -->
        <el-card shadow="never" class="section-card">
          <template #header>
            <div class="section-header">
              <span class="section-title">收货信息</span>
              <el-button link type="primary" @click="router.push('/addresses')">
                管理地址
              </el-button>
            </div>
          </template>

          <el-empty
            v-if="!loading && addresses.length === 0"
            description="暂无收货地址"
          >
            <el-button type="primary" @click="router.push('/addresses')">
              去添加地址
            </el-button>
          </el-empty>

          <div
            v-for="addr in addresses"
            :key="addr.id"
            :class="['address-option', { active: selectedAddressId === addr.id }]"
            @click="selectAddress(addr.id)"
          >
            <div class="addr-header">
              <span class="addr-name">{{ addr.contact_name }}</span>
              <span class="addr-phone">{{ addr.contact_phone }}</span>
              <el-tag v-if="addr.is_default" type="danger" size="small">默认</el-tag>
              <el-tag v-if="addr.tag" type="info" size="small">{{ addr.tag }}</el-tag>
            </div>
            <div class="addr-detail">{{ addr.full_address }}</div>
          </div>
        </el-card>

        <!-- 发票信息 -->
        <el-card shadow="never" class="section-card">
          <template #header>
            <span class="section-title">发票信息</span>
          </template>

          <el-form label-width="100px" class="invoice-form">
            <el-form-item label="发票类型">
              <el-radio-group v-model="invoiceType" @change="onInvoiceTypeChange">
                <el-radio :label="0">不开票</el-radio>
                <el-radio :label="1">电子普票</el-radio>
                <el-radio :label="2">电子专票</el-radio>
              </el-radio-group>
            </el-form-item>

            <template v-if="invoiceType !== 0">
              <el-form-item v-if="invoiceTitles.length > 0" label="常用抬头">
                <div class="title-tags">
                  <el-tag
                    v-for="t in invoiceTitles"
                    :key="t.id"
                    class="title-tag"
                    type="info"
                    effect="plain"
                    @click="selectInvoiceTitle(t)"
                  >
                    {{ t.company_name }}
                  </el-tag>
                </div>
              </el-form-item>

              <el-form-item label="发票抬头">
                <el-input v-model="invoiceTitleInput" placeholder="请输入发票抬头" />
              </el-form-item>
              <el-form-item v-if="invoiceType === 2" label="纳税人识别号">
                <el-input v-model="invoiceTaxNoInput" placeholder="请输入纳税人识别号" />
              </el-form-item>
              <el-form-item label="接收邮箱">
                <el-input v-model="invoiceEmailInput" placeholder="请输入接收邮箱" />
              </el-form-item>
            </template>
          </el-form>
        </el-card>

        <!-- 订单备注 -->
        <el-card shadow="never" class="section-card">
          <template #header>
            <span class="section-title">订单备注</span>
          </template>
          <el-input
            v-model="remark"
            type="textarea"
            :rows="3"
            placeholder="如有特殊要求请在此填写（0-500字符）"
            maxlength="500"
            show-word-limit
          />
        </el-card>
      </div>

      <!-- 中列：商品清单 -->
      <div class="col-middle">
        <el-card shadow="never" class="section-card">
          <template #header>
            <span class="section-title">商品清单</span>
          </template>

          <el-empty v-if="selectedItems.length === 0" description="购物车暂无选中商品" />

          <div
            v-for="item in selectedItems"
            :key="item.id"
            class="product-item"
          >
            <img
              :src="item.thumbnail || '/default-product.png'"
              class="product-thumb"
              alt="商品图片"
            />
            <div class="product-info">
              <div class="product-name">{{ item.product_name }}</div>
              <div class="product-meta">
                <span>单价: ¥{{ formatPrice(item.unit_price) }}</span>
                <span>数量: {{ item.quantity }}</span>
              </div>
              <div class="product-subtotal">
                小计: ¥{{ formatPrice(item.subtotal) }}
              </div>
            </div>
          </div>
        </el-card>
      </div>

      <!-- 右列：结算信息 -->
      <div class="col-right">
        <el-card shadow="never" class="section-card summary-card">
          <template #header>
            <span class="section-title">结算信息</span>
          </template>

          <!-- 优惠券 -->
          <div class="summary-row">
            <span>优惠券</span>
            <el-select
              v-model="selectedCouponCode"
              clearable
              placeholder="选择优惠券"
              class="coupon-select"
            >
              <el-option label="不使用优惠券" :value="null" />
              <el-option
                v-for="c in coupons"
                :key="c.id"
                :label="c.coupon.name + ' - ¥' + formatPrice(c.coupon.value)"
                :value="c.code"
              />
            </el-select>
          </div>

          <!-- 金额汇总 -->
          <div class="amount-summary">
            <div class="amount-row">
              <span>商品总额</span>
              <span>¥{{ formatPrice(subtotal) }}</span>
            </div>
            <div class="amount-row">
              <span>运费</span>
              <span>{{ freight === 0 ? '¥0.00（包邮）' : '¥' + formatPrice(freight) }}</span>
            </div>
            <div v-if="discount > 0" class="amount-row discount">
              <span>优惠</span>
              <span>-¥{{ formatPrice(discount) }}</span>
            </div>
            <div v-if="useBalance > 0" class="amount-row discount">
              <span>余额抵扣</span>
              <span>-¥{{ formatPrice(useBalance) }}</span>
            </div>
            <div class="amount-row total">
              <span>应付总额</span>
              <span class="total-price">¥{{ formatPrice(total) }}</span>
            </div>
          </div>

          <!-- 提交按钮 -->
          <el-button
            type="primary"
            size="large"
            class="submit-btn"
            :loading="submitting"
            :disabled="!canSubmit"
            @click="submitOrder"
          >
            提交订单
          </el-button>

          <div v-if="!selectedAddressId" class="tip-text">
            请先选择收货地址
          </div>
          <div v-else-if="selectedItems.length === 0" class="tip-text">
            购物车暂无选中商品
          </div>
        </el-card>
      </div>
    </div>
  </div>
</template>

<style scoped>
.checkout-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 320px;
  gap: 16px;
}

@media (max-width: 1200px) {
  .checkout-grid {
    grid-template-columns: 1fr;
  }
}

.section-card {
  margin-bottom: 16px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-title {
  font-weight: 600;
  font-size: 15px;
}

/* 地址选择 */
.address-option {
  padding: 12px 16px;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.2s;
}
.address-option:hover {
  border-color: #409eff;
}
.address-option.active {
  border-color: #409eff;
  background: #f0f9ff;
}
.addr-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}
.addr-name {
  font-weight: 600;
  font-size: 14px;
}
.addr-phone {
  color: #606266;
  font-size: 13px;
}
.addr-detail {
  color: #303133;
  font-size: 13px;
  line-height: 1.5;
}

/* 发票 */
.invoice-form {
  padding-top: 8px;
}
.title-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.title-tag {
  cursor: pointer;
}
.title-tag:hover {
  color: #409eff;
  border-color: #409eff;
}

/* 商品清单 */
.product-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #ebeef5;
}
.product-item:last-child {
  border-bottom: none;
}
.product-thumb {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #ebeef5;
}
.product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.product-name {
  font-weight: 500;
  font-size: 14px;
  color: #303133;
  line-height: 1.4;
}
.product-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
}
.product-subtotal {
  font-size: 14px;
  color: #f56c6c;
  font-weight: 600;
}

/* 结算信息 */
.summary-card {
  position: sticky;
  top: 16px;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.coupon-select {
  width: 180px;
}
.amount-summary {
  border-top: 1px solid #ebeef5;
  padding-top: 16px;
  margin-bottom: 16px;
}
.amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: 14px;
  color: #606266;
}
.amount-row.discount {
  color: #f56c6c;
}
.amount-row.total {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #ebeef5;
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}
.total-price {
  font-size: 24px;
  color: #f56c6c;
  font-weight: 700;
}
.submit-btn {
  width: 100%;
}
.tip-text {
  text-align: center;
  color: #f56c6c;
  font-size: 12px;
  margin-top: 8px;
}
</style>
