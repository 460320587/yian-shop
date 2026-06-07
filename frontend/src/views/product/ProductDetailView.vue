<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getProductDetail, calculatePrice, type PriceResult } from '@/api/product'
import { getFavorites, addFavorite, removeFavorite } from '@/api/favorite'
import { getProductReviews, type ReviewItem } from '@/api/review'
import { addToCart } from '@/api/cart'
import { createSampleOrder } from '@/api/sample'

const route = useRoute()
const product = ref<any>(null)
const loading = ref(false)
const isFavorited = ref(false)
const favoriteId = ref<number | null>(null)
const reviews = ref<ReviewItem[]>([])
const reviewTotal = ref(0)

// 参数选择
const selectedParams = ref({
  paper_id: null as number | null,
  color_id: null as number | null,
  quantity: 1,
  process_ids: [] as number[],
})

const priceResult = ref<PriceResult | null>(null)
const loadingPrice = ref(false)

// 样品单
const sampleDialogVisible = ref(false)
const sampleForm = ref({
  quantity: 1,
  remark: '',
})
const sampleLoading = ref(false)

// 防抖计时器
let debounceTimer: ReturnType<typeof setTimeout> | null = null

async function loadProduct() {
  loading.value = true
  try {
    product.value = await getProductDetail(Number(route.params.id))
    await checkFavoriteStatus()
    await loadReviews()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function checkFavoriteStatus() {
  try {
    const res = await getFavorites({ page: 1, per_page: 100 })
    const found = res.data.find((item) => item.product_id === Number(route.params.id))
    if (found) {
      isFavorited.value = true
      favoriteId.value = found.id
    } else {
      isFavorited.value = false
      favoriteId.value = null
    }
  } catch (e) {
    console.error(e)
  }
}

async function loadReviews() {
  try {
    const res = await getProductReviews(Number(route.params.id), { page: 1, per_page: 10 })
    reviews.value = res.data
    reviewTotal.value = res.total
  } catch (e) {
    console.error(e)
  }
}

async function toggleFavorite() {
  if (!product.value) return
  try {
    if (isFavorited.value && favoriteId.value) {
      await removeFavorite(favoriteId.value)
      ElMessage.success('已取消收藏')
      isFavorited.value = false
      favoriteId.value = null
    } else {
      const res = await addFavorite({ product_id: product.value.id })
      ElMessage.success('收藏成功')
      isFavorited.value = true
      favoriteId.value = res.id
    }
  } catch (e) {
    console.error(e)
  }
}

// 防抖实时计价
function triggerPriceCalculation() {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  debounceTimer = setTimeout(() => {
    doCalculatePrice()
  }, 500)
}

async function doCalculatePrice() {
  if (!product.value || !product.value.pricing_params) return
  if (!selectedParams.value.paper_id || !selectedParams.value.color_id || selectedParams.value.quantity < 1) return

  loadingPrice.value = true
  try {
    priceResult.value = await calculatePrice(product.value.id, {
      quantity: selectedParams.value.quantity,
      paper_id: selectedParams.value.paper_id,
      color_id: selectedParams.value.color_id,
      process_ids: selectedParams.value.process_ids,
    })
  } catch (e) {
    console.error(e)
    priceResult.value = null
  } finally {
    loadingPrice.value = false
  }
}

// 监听参数变化
watch(
  selectedParams,
  () => {
    triggerPriceCalculation()
  },
  { deep: true }
)

async function handleAddToCart() {
  if (!product.value) return
  if (!priceResult.value) {
    ElMessage.warning('请先选择参数并计算价格')
    return
  }
  try {
    await addToCart({
      product_id: product.value.id,
      quantity: selectedParams.value.quantity,
      spec_id: null,
    })
    ElMessage.success('已加入购物车')
  } catch (e) {
    console.error(e)
  }
}

function openSampleDialog() {
  sampleDialogVisible.value = true
  sampleForm.value = { quantity: 1, remark: '' }
}

function validateSampleForm(): boolean {
  if (!sampleForm.value.quantity || sampleForm.value.quantity < 1) {
    ElMessage.warning('数量至少为 1')
    return false
  }
  if (sampleForm.value.quantity > 100) {
    ElMessage.warning('样品数量不能超过 100')
    return false
  }
  return true
}

async function submitSampleOrder() {
  if (!product.value) return
  if (!validateSampleForm()) return
  sampleLoading.value = true
  try {
    await createSampleOrder({
      product_id: product.value.id,
      quantity: sampleForm.value.quantity,
      remark: sampleForm.value.remark || undefined,
    })
    ElMessage.success('样品订单已提交')
    sampleDialogVisible.value = false
  } catch (e) {
    console.error(e)
  } finally {
    sampleLoading.value = false
  }
}

onMounted(() => {
  loadProduct()
})

onUnmounted(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
})

defineExpose({
  product,
  loading,
  isFavorited,
  favoriteId,
  reviews,
  reviewTotal,
  toggleFavorite,
  checkFavoriteStatus,
  loadReviews,
  selectedParams,
  priceResult,
  loadingPrice,
  handleAddToCart,
  sampleDialogVisible,
  sampleForm,
  sampleLoading,
  openSampleDialog,
  validateSampleForm,
  submitSampleOrder,
})
</script>

<template>
  <div class="product-detail-view page-container">
    <div v-loading="loading">
      <div v-if="product" class="product-main">
        <!-- 商品头部 -->
        <div class="product-header">
          <h2>{{ product.name }}</h2>
          <el-button
            :type="isFavorited ? 'danger' : 'default'"
            @click="toggleFavorite"
          >
            {{ isFavorited ? '已收藏' : '收藏' }}
          </el-button>
        </div>
        <p class="product-description">{{ product.description }}</p>

        <!-- 参数选择区 -->
        <div v-if="product.pricing_params" class="param-form-section">
          <!-- 纸张选择 -->
          <div class="param-row paper-options">
            <label class="param-label">纸张类型：</label>
            <el-radio-group v-model="selectedParams.paper_id">
              <el-radio-button
                v-for="opt in product.pricing_params.paper_options"
                :key="opt.id"
                :label="opt.id"
              >
                {{ opt.name }}
              </el-radio-button>
            </el-radio-group>
          </div>

          <!-- 颜色选择 -->
          <div class="param-row color-options">
            <label class="param-label">印刷颜色：</label>
            <el-radio-group v-model="selectedParams.color_id">
              <el-radio-button
                v-for="opt in product.pricing_params.color_options"
                :key="opt.id"
                :label="opt.id"
              >
                {{ opt.name }}
              </el-radio-button>
            </el-radio-group>
          </div>

          <!-- 数量输入 -->
          <div class="param-row quantity-input">
            <label class="param-label">数量：</label>
            <el-input-number v-model="selectedParams.quantity" :min="1" :max="100000" />
            <span v-if="product.pricing_params.unit" class="unit-text">{{ product.pricing_params.unit }}</span>
          </div>

          <!-- 工艺选择 -->
          <div v-if="product.pricing_params.process_options?.length" class="param-row process-options">
            <label class="param-label">加工工艺：</label>
            <el-checkbox-group v-model="selectedParams.process_ids">
              <el-checkbox
                v-for="opt in product.pricing_params.process_options"
                :key="opt.id"
                :label="opt.id"
              >
                {{ opt.name }}
              </el-checkbox>
            </el-checkbox-group>
          </div>

          <!-- 实时计价 -->
          <div class="price-section">
            <div v-if="priceResult" class="price-result">
              <div class="price-main">
                <span class="price-label">单价：</span>
                <span class="price-value">¥{{ priceResult.unit_price.toFixed(2) }}</span>
                <span class="price-unit">/{{ product.pricing_params.unit }}</span>
              </div>
              <el-collapse>
                <el-collapse-item title="计价明细">
                  <div class="breakdown-item">
                    <span>基础金额：</span>
                    <span>¥{{ priceResult.breakdown.base_amount.toFixed(2) }}</span>
                  </div>
                  <div v-if="priceResult.breakdown.process_amount > 0" class="breakdown-item">
                    <span>工艺费用：</span>
                    <span>¥{{ priceResult.breakdown.process_amount.toFixed(2) }}</span>
                  </div>
                  <div class="breakdown-item breakdown-total">
                    <span>合计：</span>
                    <span>¥{{ priceResult.breakdown.total_amount.toFixed(2) }}</span>
                  </div>
                </el-collapse-item>
              </el-collapse>
              <div class="total-price">
                <span>总价：</span>
                <span class="total-value">¥{{ priceResult.breakdown.total_amount.toFixed(2) }}</span>
              </div>
            </div>
            <div v-else-if="loadingPrice" class="price-loading">
              计算中...
            </div>
            <div v-else class="price-hint">
              请选择参数查看价格
            </div>
          </div>

          <!-- 加入购物车 / 样品单 -->
          <div class="action-row">
            <el-button type="primary" size="large" @click="handleAddToCart">
              加入购物车
            </el-button>
            <el-button type="success" size="large" @click="openSampleDialog">
              下样品单
            </el-button>
          </div>
        </div>

        <div v-else class="no-params-tip">
          <el-alert title="该商品暂无规格参数，请联系客服咨询" type="info" :closable="false" />
        </div>
      </div>

      <!-- 样品单对话框 -->
    <el-dialog v-model="sampleDialogVisible" title="下样品单" width="480px">
      <el-form :model="sampleForm" label-width="80px">
        <el-form-item label="数量" required>
          <el-input-number v-model="sampleForm.quantity" :min="1" :max="100" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="sampleForm.remark" type="textarea" :rows="2" placeholder="选填：如加急、指定颜色等" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="sampleDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="sampleLoading" @click="submitSampleOrder">确认</el-button>
      </template>
    </el-dialog>

    <!-- 评价区 -->
      <div v-if="reviews.length > 0" class="review-section">
        <h3>用户评价（{{ reviewTotal }}）</h3>
        <div v-for="review in reviews" :key="review.id" class="review-item">
          <div class="review-header">
            <span class="reviewer">{{ review.customer?.nickname || '匿名用户' }}</span>
            <el-rate v-model="review.rating" disabled />
            <span class="review-date">{{ review.created_at }}</span>
          </div>
          <div class="review-content">{{ review.content }}</div>
          <div v-if="review.reply" class="review-reply">
            <strong>商家回复：</strong>{{ review.reply }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-detail-view {
  padding: 20px;
}
.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.product-description {
  color: #606266;
  margin-bottom: 24px;
  line-height: 1.6;
}
.param-form-section {
  background: #f5f7fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 24px;
}
.param-row {
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.param-label {
  font-weight: 600;
  color: #303133;
  min-width: 80px;
}
.unit-text {
  color: #606266;
  margin-left: 8px;
}
.price-section {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
}
.price-result {
  margin-bottom: 16px;
}
.price-main {
  font-size: 16px;
  margin-bottom: 8px;
}
.price-value {
  color: #f56c6c;
  font-size: 24px;
  font-weight: 700;
}
.price-unit {
  color: #909399;
  font-size: 14px;
}
.breakdown-item {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
  color: #606266;
}
.breakdown-total {
  font-weight: 600;
  color: #303133;
  border-top: 1px solid #e4e7ed;
  padding-top: 8px;
  margin-top: 4px;
}
.total-price {
  margin-top: 12px;
  font-size: 18px;
}
.total-value {
  color: #f56c6c;
  font-size: 28px;
  font-weight: 700;
}
.price-loading {
  color: #909399;
  padding: 12px 0;
}
.price-hint {
  color: #909399;
  padding: 12px 0;
}
.action-row {
  margin-top: 20px;
}
.no-params-tip {
  margin-bottom: 24px;
}
.review-section {
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid #ebeef5;
}
.review-section h3 {
  margin-bottom: 16px;
  font-size: 18px;
}
.review-item {
  padding: 16px 0;
  border-bottom: 1px solid #ebeef5;
}
.review-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.reviewer {
  font-weight: 600;
  color: #303133;
}
.review-date {
  color: #909399;
  font-size: 13px;
}
.review-content {
  color: #606266;
  line-height: 1.6;
  margin-bottom: 8px;
}
.review-reply {
  background: #f5f7fa;
  padding: 10px 12px;
  border-radius: 4px;
  color: #606266;
  font-size: 14px;
}
</style>
