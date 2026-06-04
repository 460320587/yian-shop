<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getCart, updateCartItem, removeCartItem, clearCart } from '@/api/cart'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { CartItem } from '@/api/cart'

const router = useRouter()
const cartItems = ref<CartItem[]>([])
const summary = ref({ total_count: 0, selected_count: 0, selected_subtotal: 0 })
const loading = ref(false)

onMounted(() => {
  loadCart()
})

async function loadCart() {
  loading.value = true
  try {
    const data = await getCart()
    cartItems.value = data.items || []
    summary.value = data.summary || { total_count: 0, selected_count: 0, selected_subtotal: 0 }
  } catch (e) {
    console.error('购物车加载失败', e)
  }
  loading.value = false
}

const totalPrice = computed(() => summary.value.selected_subtotal.toFixed(2))

async function updateQuantity(item: CartItem) {
  try {
    await updateCartItem(item.id, { quantity: item.quantity })
    loadCart()
  } catch {
    ElMessage.error('更新失败')
  }
}

async function toggleSelect(item: CartItem) {
  try {
    await updateCartItem(item.id, { selected: !item.selected })
    loadCart()
  } catch {
    ElMessage.error('操作失败')
  }
}

async function removeItem(item: CartItem) {
  try {
    await removeCartItem(item.id)
    ElMessage.success('已删除')
    loadCart()
  } catch {
    ElMessage.error('删除失败')
  }
}

async function handleClearCart() {
  try {
    await ElMessageBox.confirm('确定清空购物车吗？', '提示', { type: 'warning' })
    await clearCart()
    ElMessage.success('购物车已清空')
    loadCart()
  } catch {
    // 取消
  }
}

function checkout() {
  if (summary.value.selected_count === 0) {
    ElMessage.warning('请至少选择一件商品')
    return
  }
  router.push('/checkout')
}

function goProductDetail(id: number) {
  router.push(`/product/${id}`)
}
</script>

<template>
  <div class="cart-page page-container">
    <el-card class="cart-card" v-loading="loading">
      <template #header>
        <div class="cart-header">
          <span class="title">购物车</span>
          <el-button type="danger" text @click="handleClearCart" :disabled="!cartItems.length">
            清空购物车
          </el-button>
        </div>
      </template>

      <el-empty v-if="!cartItems.length" description="购物车是空的" />

      <div v-else>
        <div v-for="item in cartItems" :key="item.id" class="cart-item">
          <el-checkbox v-model="item.selected" @change="toggleSelect(item)" />
          <el-image
            :src="item.thumbnail || 'https://placehold.co/80x80/e4e7ed/999?text=图'"
            fit="cover"
            class="item-img"
            @click="goProductDetail(item.product_id)"
          />
          <div class="item-info" @click="goProductDetail(item.product_id)">
            <p class="item-name">{{ item.product_name }}</p>
            <p v-if="item.spec_info" class="item-spec">{{ item.spec_info }}</p>
          </div>
          <div class="item-price">¥{{ item.unit_price.toFixed(2) }}</div>
          <el-input-number v-model="item.quantity" :min="1" :max="99" size="small" @change="updateQuantity(item)" />
          <div class="item-subtotal">¥{{ item.subtotal.toFixed(2) }}</div>
          <el-button type="danger" text @click="removeItem(item)">删除</el-button>
        </div>

        <div class="cart-footer">
          <div class="total">
            已选 {{ summary.selected_count }} 件，合计:
            <span class="total-price">¥{{ totalPrice }}</span>
          </div>
          <el-button type="primary" size="large" @click="checkout">去结算</el-button>
        </div>
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.cart-page {
  max-width: 1200px;
  margin: 20px auto;
}
.cart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.title {
  font-size: 18px;
  font-weight: bold;
}
.cart-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid var(--border-light);
}
.item-img {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  cursor: pointer;
}
.item-info {
  flex: 1;
  cursor: pointer;
}
.item-name {
  font-size: 14px;
  color: var(--text-primary);
}
.item-spec {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}
.item-price, .item-subtotal {
  font-size: 14px;
  color: var(--text-primary);
  min-width: 80px;
  text-align: center;
}
.item-subtotal {
  font-weight: bold;
  color: var(--color-primary);
}
.cart-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 20px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--border-light);
}
.total {
  font-size: 16px;
}
.total-price {
  color: var(--color-primary);
  font-size: 24px;
  font-weight: bold;
}
</style>
