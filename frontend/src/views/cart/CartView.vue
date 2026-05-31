<template>
  <MainLayout>
    <div class="cart-page">
      <el-card class="cart-card">
        <template #header>
          <div class="cart-header">
            <span class="title">购物车</span>
            <el-button type="danger" text @click="clearCart">清空购物车</el-button>
          </div>
        </template>

        <el-empty v-if="!cartItems.length" description="购物车是空的" />

        <el-table v-else :data="cartItems" style="width: 100%">
          <el-table-column label="商品" min-width="200">
            <template #default="{ row }">
              <div class="product-cell">
                <el-image :src="row.image" fit="cover" class="product-img" />
                <span>{{ row.name }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="规格" prop="spec" width="150" />
          <el-table-column label="单价" width="120">
            <template #default="{ row }">
              <span class="price">¥{{ row.price }}</span>
            </template>
          </el-table-column>
          <el-table-column label="数量" width="150">
            <template #default="{ row }">
              <el-input-number v-model="row.quantity" :min="1" :max="99" size="small" />
            </template>
          </el-table-column>
          <el-table-column label="小计" width="120">
            <template #default="{ row }">
              <span class="price">¥{{ (row.price * row.quantity).toFixed(2) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="100">
            <template #default="{ $index }">
              <el-button type="danger" text @click="removeItem($index)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>

        <div v-if="cartItems.length" class="cart-footer">
          <div class="total">
            合计: <span class="total-price">¥{{ totalPrice }}</span>
          </div>
          <el-button type="primary" size="large" @click="checkout">去结算</el-button>
        </div>
      </el-card>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/components/Layout/MainLayout.vue'

const router = useRouter()

const cartItems = ref([
  { id: 1, name: '铜版纸名片', spec: '300g铜版纸', price: 25.00, quantity: 2, image: '' },
  { id: 2, name: '企业画册', spec: 'A4, 16P', price: 120.00, quantity: 1, image: '' },
])

const totalPrice = computed(() =>
  cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0).toFixed(2)
)

const removeItem = (index: number) => {
  cartItems.value.splice(index, 1)
}

const clearCart = () => {
  cartItems.value = []
}

const checkout = () => {
  router.push('/orders/create')
}
</script>

<style scoped>
.cart-page {
  max-width: 1200px;
  margin: 20px auto;
  padding: 0 20px;
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
.product-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.product-img {
  width: 60px;
  height: 60px;
  border-radius: 4px;
  background: #f5f5f5;
}
.price {
  color: #f56c6c;
  font-weight: bold;
}
.cart-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 20px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #ebeef5;
}
.total {
  font-size: 16px;
}
.total-price {
  color: #f56c6c;
  font-size: 24px;
  font-weight: bold;
}
</style>
