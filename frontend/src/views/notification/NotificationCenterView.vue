<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import {
  getNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  type NotificationItem,
} from '@/api/notification'

const notifications = ref<NotificationItem[]>([])
const loading = ref(false)
const currentPage = ref(1)
const total = ref(0)
const filterRead = ref<number | undefined>(undefined)

const hasUnread = computed(() => notifications.value.some((n) => n.is_read === 0))

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
        :class="['notification-item', { unread: item.is_read === 0 }]"
      >
        <div class="notification-header">
          <span class="notification-title">{{ item.title }}</span>
          <span class="notification-time">{{ item.created_at }}</span>
        </div>
        <div class="notification-content">{{ item.content }}</div>
        <div class="notification-actions">
          <el-button
            v-if="item.is_read === 0"
            link
            type="primary"
            size="small"
            @click="handleMarkRead(item.id)"
          >
            标记已读
          </el-button>
          <router-link
            v-if="item.action_url"
            :to="item.action_url"
            class="action-link"
          >
            {{ item.action_text || '查看详情' }}
          </router-link>
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
  padding: 16px;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
}
.notification-item.unread {
  background: #f5f7fa;
}
.notification-item:hover {
  background: #ecf5ff;
}
.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.notification-title {
  font-weight: 600;
  font-size: 15px;
}
.notification-time {
  color: #909399;
  font-size: 13px;
}
.notification-content {
  color: #606266;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 8px;
}
.notification-actions {
  display: flex;
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
