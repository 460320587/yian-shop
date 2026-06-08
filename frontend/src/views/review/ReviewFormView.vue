<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getProductDetail } from '@/api/product'
import { submitReview, uploadReviewImages } from '@/api/review'

const route = useRoute()
const router = useRouter()

const product = ref<any>(null)
const loading = ref(false)
const errorMsg = ref('')

const orderId = ref<number>(0)
const productId = ref<number>(0)

interface FormState {
  rating: number
  content: string
  images: string[]
}

const form = ref<FormState>({
  rating: 5,
  content: '',
  images: [],
})

const uploadLoading = ref(false)

async function handleUpload(options: any) {
  const formData = new FormData()
  formData.append('images', options.file)
  uploadLoading.value = true
  try {
    const res = await uploadReviewImages(formData)
    form.value.images.push(...res.urls)
    options.onSuccess?.(res)
  } catch (e) {
    options.onError?.(e)
  } finally {
    uploadLoading.value = false
  }
}

async function loadProduct() {
  const oid = Number(route.query.orderId)
  const pid = Number(route.query.productId)

  if (!oid || !pid) {
    errorMsg.value = '参数缺失，请从订单页面进入评价'
    return
  }

  orderId.value = oid
  productId.value = pid

  loading.value = true
  try {
    product.value = await getProductDetail(pid)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function validateForm(): Promise<boolean> {
  if (form.value.rating < 1 || form.value.rating > 5) {
    ElMessage.error('请选择评分')
    return false
  }
  if (!form.value.content || form.value.content.length < 5) {
    ElMessage.error('评价内容至少 5 个字符')
    return false
  }
  if (form.value.content.length > 500) {
    ElMessage.error('评价内容最多 500 个字符')
    return false
  }
  return true
}

async function handleSubmit() {
  const valid = await validateForm()
  if (!valid) return

  loading.value = true
  try {
    await submitReview(orderId.value, {
      product_id: productId.value,
      rating: form.value.rating,
      content: form.value.content,
      images: form.value.images.length > 0 ? form.value.images : undefined,
    })
    ElMessage.success('评价提交成功')
    router.push('/orders')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProduct()
})

defineExpose({
  product,
  loading,
  errorMsg,
  form,
  orderId,
  productId,
  validateForm,
  submitReview: handleSubmit,
  handleUpload,
})
</script>

<template>
  <div class="review-form-view page-container">
    <h2 class="page-title">商品评价</h2>

    <div v-if="errorMsg" class="error-box">
      {{ errorMsg }}
    </div>

    <div v-else v-loading="loading">
      <div v-if="product" class="product-info">
        <img v-if="product.thumbnail" :src="product.thumbnail" class="product-thumb" />
        <div>
          <div class="product-name">{{ product.name }}</div>
          <div class="product-price">
            ¥{{ (product.price_min / 100).toFixed(2) }} ~ ¥{{ (product.price_max / 100).toFixed(2) }}
          </div>
        </div>
      </div>

      <el-form label-width="80px" class="review-form">
        <el-form-item label="评分">
          <el-rate v-model="form.rating" :max="5" />
        </el-form-item>
        <el-form-item label="评价内容">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="4"
            placeholder="请输入评价内容（5-500字）"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="上传图片">
          <el-upload
            action="#"
            :http-request="handleUpload"
            list-type="picture-card"
            :limit="9"
            accept="image/jpeg,image/png,image/gif,image/webp"
          >
            <el-icon><Plus /></el-icon>
          </el-upload>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="handleSubmit">
            提交评价
          </el-button>
          <el-button @click="$router.back()">取消</el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<style scoped>
.review-form-view {
  max-width: 640px;
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
.product-info {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 8px;
  margin-bottom: 20px;
}
.product-thumb {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  object-fit: cover;
}
.product-name {
  font-weight: 600;
  font-size: 16px;
  margin-bottom: 4px;
}
.product-price {
  color: #f56c6c;
  font-size: 14px;
}
.review-form {
  margin-top: 16px;
}
</style>
