<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getCart, updateCartItem, removeCartItem, clearCart } from '@/api/cart'
import type { CartItem, CartData } from '@/api/cart'

const router = useRouter()

const cartItems = ref<CartItem[]>([])
const summary = ref<CartData['summary']>({ total_count: 0, selected_count: 0, selected_subtotal: 0 })
const loading = ref(false)

const allSelected = computed(() => {
  return cartItems.value.length > 0 && cartItems.value.every(item => item.selected)
})

async function loadCart() {
  loading.value = true
  try {
    const res = await getCart()
    cartItems.value = res.items
    summary.value = res.summary
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function updateQuantity(item: CartItem, quantity: number) {
  if (quantity < 1) return
  loading.value = true
  try {
    await updateCartItem(item.id, { quantity })
    await loadCart()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function toggleSelection(item: CartItem, selected: boolean) {
  loading.value = true
  try {
    await updateCartItem(item.id, { selected })
    await loadCart()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function toggleSelectAll(selected: boolean) {
  loading.value = true
  try {
    const promises = cartItems.value.map(item => updateCartItem(item.id, { selected }))
    await Promise.all(promises)
    await loadCart()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function removeItem(item: CartItem) {
  if (!confirm('确定要删除该商品吗？')) return
  loading.value = true
  try {
    await removeCartItem(item.id)
    ElMessage.success('已删除')
    await loadCart()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function clearCartItems() {
  if (!confirm('确定要清空购物车吗？')) return
  loading.value = true
  try {
    await clearCart()
    ElMessage.success('购物车已清空')
    await loadCart()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goCheckout() {
  if (summary.value.selected_count === 0) {
    ElMessage.warning('请至少选择一件商品')
    return
  }
  router.push('/checkout')
}

function formatPrice(price: number): string {
  return (price / 100).toFixed(2)
}

onMounted(() => {
  loadCart()
})

defineExpose({
  cartItems,
  summary,
  loading,
  allSelected,
  loadCart,
  updateQuantity,
  toggleSelection,
  toggleSelectAll,
  removeItem,
  clearCart: clearCartItems,
  goCheckout,
})
</script>

<template>
  <div class="cart-view page-container">
    <div class="header-row">
      <h2 class="page-title">购物车</h2>
      <el-button v-if="cartItems.length > 0" link type="danger" size="small" @click="clearCartItems">清空购物车</el-button>
    </div>

    <div v-loading="loading" class="cart-content">
      <div v-if="cartItems.length > 0" class="cart-list">
        <div class="cart-header">
          <el-checkbox :model-value="allSelected" @change="toggleSelectAll">全选</el-checkbox>
          <span class="col-name">商品</span>
          <span class="col-price">单价</span>
          <span class="col-quantity">数量</span>
          <span class="col-subtotal">小计</span>
          <span class="col-action">操作</span>
        </div>

        <div v-for="item in cartItems" :key="item.id" class="cart-item-row">
          <el-checkbox :model-value="item.selected" @change="(val: any) => toggleSelection(item, val)" />
          <div class="item-info">
            <div class="item-name">{{ item.product_name }}</div>
          </div>
          <div class="item-price">¥{{ formatPrice(item.unit_price) }}</div>
          <div class="item-quantity">
            <el-input-number v-model="item.quantity" :min="1" size="small" @change="(val: number) => updateQuantity(item, val)" />
          </div>
          <div class="item-subtotal">¥{{ formatPrice(item.subtotal) }}</div>
          <div class="item-action">
            <el-button link type="danger" size="small" @click="removeItem(item)">删除</el-button>
          </div>
        </div>

        <div class="cart-summary">
          <span>已选 {{ summary.selected_count }} 件</span>
          <span class="summary-amount">合计：¥{{ formatPrice(summary.selected_subtotal) }}</span>
          <el-button data-testid="checkout-btn" type="primary" @click="goCheckout">去结算</el-button>
        </div>
      </div>

      <div v-else class="empty-state">
        购物车是空的，快去选购商品吧
      </div>
    </div>
  </div>
</template>

<style scoped>
.cart-view {
  max-width: 800px;
  margin: 20px auto;
  padding: 20px;
}
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.page-title {
  margin: 0;
}
.cart-content {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.cart-header {
  display: grid;
  grid-template-columns: 40px 2fr 1fr 120px 1fr 80px;
  align-items: center;
  padding: 12px;
  background: #f5f7fa;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  margin-bottom: 8px;
}
.cart-item-row {
  display: grid;
  grid-template-columns: 40px 2fr 1fr 120px 1fr 80px;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #ebeef5;
}
.item-name {
  font-size: 14px;
}
.item-price,
.item-subtotal {
  font-size: 14px;
  color: #f56c6c;
  font-weight: 600;
}
.cart-summary {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 20px;
  padding: 16px 12px;
  margin-top: 8px;
  border-top: 1px solid #ebeef5;
}
.summary-amount {
  font-size: 18px;
  color: #f56c6c;
  font-weight: 700;
}
.empty-state {
  padding: 60px;
  text-align: center;
  color: #909399;
}
</style>
