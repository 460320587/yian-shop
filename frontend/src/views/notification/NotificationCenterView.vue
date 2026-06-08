<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Document,
  Money,
  Warning,
  Bell,
  Present,
  Delete,
} from '@element-plus/icons-vue'
import {
  getNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  deleteNotification,
  type NotificationItem,
} from '@/api/notification'

const notifications = ref<NotificationItem[]>([])
const loading = ref(false)
const currentPage = ref(1)
const total = ref(0)
const filterRead = ref<number | undefined>(undefined)

const hasUnread = computed(() => notifications.value.some((n) => n.is_read === 0))

interface TypeMeta {
  icon: any
  color: string
  bg: string
  label: string
}

const typeMap: Record<string, TypeMeta> = {
  order: { icon: Document, color: '#409eff', bg: '#ecf5ff', label: '订单' },
  payment: { icon: Money, color: '#67c23a', bg: '#f0f9eb', label: '支付' },
  system: { icon: Warning, color: '#e6a23c', bg: '#fdf6ec', label: '系统' },
  promotion: { icon: Present, color: '#f56c6c', bg: '#fef0f0', label: '促销' },
}

function getTypeMeta(type: string): TypeMeta {
  return typeMap[type] || { icon: Bell, color: '#909399', bg: '#f4f4f5', label: '通知' }
}

function formatRelativeTime(time: string): string {
  if (!time) return '-'
  const now = new Date().getTime()
  const then = new Date(time.replace(/-/g, '/')).getTime()
  if (Number.isNaN(then)) return time
  const diff = Math.max(0, now - then)
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (seconds < 60) return '刚刚'
  if (minutes < 60) return `${minutes}分钟前`
  if (hours < 24) return `${hours}小时前`
  if (days < 30) return `${days}天前`
  const d = new Date(then)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

async function fetchNotifications() {
  loading.value = true
  try {
    const res = await getNotifications({
      page: currentPage.value,
      per_page: 10,
      is_read: filterRead.value,
    })
    notifications.value = res.data
    total.value = res.total
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function handleMarkRead(id: number) {
  try {
    await markNotificationRead(id)
    const target = notifications.value.find((n) => n.id === id)
    if (target) target.is_read = 1
    ElMessage.success('标记已读')
  } catch (e) {
    console.error(e)
  }
}

async function handleMarkAllRead() {
  try {
    await markAllNotificationsRead()
    notifications.value.forEach((n) => (n.is_read = 1))
    ElMessage.success('全部已读')
  } catch (e) {
    console.error(e)
  }
}

async function handleItemClick(item: NotificationItem) {
  if (item.is_read === 0) {
    await handleMarkRead(item.id)
  }
}

async function handleDelete(id: number) {
  try {
    await ElMessageBox.confirm('确定删除这条通知吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteNotification(id)
    notifications.value = notifications.value.filter((n) => n.id !== id)
    total.value = Math.max(0, total.value - 1)
    ElMessage.success('删除成功')
  } catch (e: any) {
    if (e !== 'cancel' && e?.message !== 'cancel') {
      console.error(e)
    }
  }
}

function handlePageChange(page: number) {
  currentPage.value = page
  fetchNotifications()
}

function handleFilterChange() {
  currentPage.value = 1
  fetchNotifications()
}

onMounted(() => {
  fetchNotifications()
})

defineExpose({
  notifications,
  loading,
  hasUnread,
  filterRead,
  currentPage,
  total,
  fetchNotifications,
  handleMarkRead,
  handleMarkAllRead,
  handleItemClick,
  handleDelete,
  formatRelativeTime,
  getTypeMeta,
})
</script>

<template>
  <div class="notification-center-view page-container">
    <h2 class="page-title">消息通知</h2>

    <div class="toolbar">
      <el-radio-group v-model="filterRead" @change="handleFilterChange">
        <el-radio-button :label="undefined">全部</el-radio-button>
        <el-radio-button :label="0">未读</el-radio-button>
        <el-radio-button :label="1">已读</el-radio-button>
      </el-radio-group>
      <el-button v-if="hasUnread" type="primary" size="small" @click="handleMarkAllRead">
        全部已读
      </el-button>
    </div>

    <div v-loading="loading" class="notification-list">
      <el-empty v-if="!loading && notifications.length === 0" description="暂无通知" />

      <div
        v-for="item in notifications"
        :key="item.id"
        :class="['notification-item', { unread: item.is_read === 0 }, 'type-' + item.type]"
        @click="handleItemClick(item)"
      >
        <div class="notification-icon-wrapper">
          <el-icon :size="22" :color="getTypeMeta(item.type).color">
            <component :is="getTypeMeta(item.type).icon" />
          </el-icon>
        </div>
        <div class="notification-body">
          <div class="notification-header">
            <span class="notification-title">{{ item.title }}</span>
            <span class="notification-time">{{ formatRelativeTime(item.created_at) }}</span>
          </div>
          <div class="notification-content">{{ item.content }}</div>
          <div class="notification-actions">
            <el-button
              v-if="item.is_read === 0"
              link
              type="primary"
              size="small"
              @click.stop="handleMarkRead(item.id)"
            >
              标记已读
            </el-button>
            <router-link
              v-if="item.action_url"
              :to="item.action_url"
              class="action-link"
              @click.stop
            >
              {{ item.action_text || '查看详情' }}
            </router-link>
            <el-button
              data-testid="delete-notification-btn"
              link
              type="danger"
              size="small"
              @click.stop="handleDelete(item.id)"
            >
              <el-icon><Delete /></el-icon>
              删除
            </el-button>
          </div>
        </div>
      </div>
    </div>

    <el-pagination
      v-if="total > 10"
      v-model:current-page="currentPage"
      :page-size="10"
      :total="total"
      layout="prev, pager, next"
      @current-change="handlePageChange"
    />
  </div>
</template>

<style scoped>
.notification-center-view {
  padding: 20px;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.notification-list {
  min-height: 200px;
}
.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
  cursor: pointer;
}
.notification-item.unread {
  background: #f5f7fa;
}
.notification-item:hover {
  background: #ecf5ff;
}
.notification-icon-wrapper {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: #f4f4f5;
}
.notification-item.type-order .notification-icon-wrapper {
  background: #ecf5ff;
}
.notification-item.type-payment .notification-icon-wrapper {
  background: #f0f9eb;
}
.notification-item.type-system .notification-icon-wrapper {
  background: #fdf6ec;
}
.notification-item.type-promotion .notification-icon-wrapper {
  background: #fef0f0;
}
.notification-body {
  flex: 1;
  min-width: 0;
}
.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}
.notification-title {
  font-weight: 600;
  font-size: 15px;
}
.notification-time {
  color: #909399;
  font-size: 12px;
}
.notification-content {
  color: #606266;
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: 8px;
}
.notification-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
.action-link {
  font-size: 13px;
  color: #409eff;
  text-decoration: none;
}
.action-link:hover {
  text-decoration: underline;
}
</style>
