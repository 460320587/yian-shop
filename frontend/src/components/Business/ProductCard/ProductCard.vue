<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { formatPrice } from '@/utils/format'

export interface ProductCardProps {
  id: number
  name: string
  thumbnail?: string | null
  minPrice?: number | null
  maxPrice?: number | null
  salesCount?: number
  category?: { id: number; name: string } | null
  isHot?: boolean
  isNew?: boolean
  size?: 'default' | 'small'
}

const props = withDefaults(defineProps<ProductCardProps>(), {
  thumbnail: null,
  minPrice: null,
  maxPrice: null,
  salesCount: 0,
  category: null,
  isHot: false,
  isNew: false,
  size: 'default',
})

const router = useRouter()

const priceText = computed(() => {
  const min = props.minPrice
  const max = props.maxPrice
  if (min === null && max === null) return '暂无报价'
  if (min === null) return formatPrice(max as number)
  if (max === null || min === max) return formatPrice(min)
  return `${formatPrice(min)} - ${formatPrice(max)}`
})

const salesText = computed(() => {
  const count = props.salesCount || 0
  if (count >= 10000) return `已售 ${(count / 10000).toFixed(1)}万`
  if (count >= 1000) return `已售 ${(count / 1000).toFixed(1)}k`
  return `已售 ${count}`
})

function handleClick() {
  router.push(`/product/${props.id}`)
}
</script>

<template>
  <div
    :class="['product-card', `size-${size}`]"
    @click="handleClick"
  >
    <div class="product-image">
      <img v-if="thumbnail" :src="thumbnail" :alt="name" loading="lazy">
      <div v-else class="image-placeholder">
        <span>{{ name.slice(0, 1) }}</span>
      </div>
      <div class="product-tags">
        <span v-if="isHot" class="tag tag-hot">热销</span>
        <span v-if="isNew" class="tag tag-new">新品</span>
      </div>
    </div>

    <div class="product-info">
      <h4 class="product-name" :title="name">{{ name }}</h4>
      <div class="product-meta">
        <span v-if="category" class="product-category">{{ category.name }}</span>
        <span class="product-sales">{{ salesText }}</span>
      </div>
      <div class="product-price">
        <span class="price-label">¥</span>
        <span class="price-value">{{ priceText.replace(/¥/g, '') }}</span>
        <span class="price-unit">起</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-card {
  background: var(--bg-card);
  border-radius: var(--radius-large);
  overflow: hidden;
  box-shadow: var(--shadow-light);
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-base);
}

.product-image {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  background: var(--bg-body);
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
  transform: scale(1.05);
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f0f2f5 0%, #e4e7ed 100%);
  color: var(--text-muted);
  font-size: 48px;
  font-weight: 600;
}

.product-tags {
  position: absolute;
  top: 8px;
  left: 8px;
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.tag {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  color: #fff;
}

.tag-hot {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
}

.tag-new {
  background: linear-gradient(135deg, #67c23a 0%, #5daf34 100%);
}

.product-info {
  padding: 14px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.product-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.5;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 42px;
}

.product-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  color: var(--text-muted);
}

.product-category {
  background: var(--border-light);
  padding: 2px 8px;
  border-radius: 10px;
}

.product-price {
  margin-top: auto;
  display: flex;
  align-items: baseline;
  gap: 2px;
  color: var(--color-danger);
}

.price-label {
  font-size: 12px;
  font-weight: 600;
}

.price-value {
  font-size: 18px;
  font-weight: 700;
}

.price-unit {
  font-size: 12px;
  color: var(--text-muted);
  margin-left: 2px;
}

/* small size variant */
.size-small .product-info {
  padding: 10px;
}

.size-small .product-name {
  font-size: 13px;
  min-height: 38px;
}

.size-small .price-value {
  font-size: 15px;
}
</style>
