<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
import { updateProfile } from '@/api/profile'

const router = useRouter()
const userStore = useUserStore()

const submitting = ref(false)
const avatarPreview = ref('')
const fileInputRef = ref<HTMLInputElement | null>(null)

interface FormState {
  nickname: string
  avatar: string
  link_person: string
  qq: string
}

const form = ref<FormState>({
  nickname: '',
  avatar: '',
  link_person: '',
  qq: '',
})

function initForm() {
  const user = userStore.userInfo
  if (user) {
    form.value.nickname = user.nickname || ''
    form.value.avatar = user.avatar || ''
    avatarPreview.value = user.avatar || ''
  }
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    ElMessage.error('图片大小不能超过2MB')
    return
  }

  const reader = new FileReader()
  reader.onload = () => {
    const result = reader.result as string
    handleFileSelect(result)
  }
  reader.readAsDataURL(file)
}

function handleFileSelect(base64: string) {
  form.value.avatar = base64
  avatarPreview.value = base64
}

function clearAvatar() {
  form.value.avatar = ''
  avatarPreview.value = ''
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function triggerFileInput() {
  fileInputRef.value?.click()
}

async function handleSubmit() {
  if (form.value.nickname.length > 50) {
    ElMessage.error('昵称最多50个字符')
    return
  }
  if (form.value.avatar && form.value.avatar.length > 200000) {
    ElMessage.error('头像图片过大')
    return
  }
  if (form.value.link_person && form.value.link_person.length > 50) {
    ElMessage.error('联系人最多50个字符')
    return
  }
  if (form.value.qq && form.value.qq.length > 20) {
    ElMessage.error('QQ最多20个字符')
    return
  }

  submitting.value = true
  try {
    const res = await updateProfile({
      nickname: form.value.nickname || undefined,
      avatar: form.value.avatar || undefined,
      link_person: form.value.link_person || undefined,
      qq: form.value.qq || undefined,
    })
    userStore.setUserInfo(res)
    ElMessage.success('资料已更新')
    router.back()
  } catch (e) {
    console.error(e)
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(() => {
  initForm()
})

defineExpose({
  form,
  submitting,
  avatarPreview,
  fileInputRef,
  handleSubmit,
  goBack,
  handleFileSelect,
  clearAvatar,
  triggerFileInput,
})
</script>

<template>
  <div class="profile-edit-view page-container">
    <h2 class="page-title">编辑资料</h2>

    <div class="edit-panel">
      <el-form label-width="100px">
        <el-form-item label="手机号">
          <span class="phone-text">{{ userStore.userInfo?.phone }}</span>
        </el-form-item>

        <el-form-item label="头像">
          <div class="avatar-upload">
            <div v-if="avatarPreview" class="avatar-preview">
              <img :src="avatarPreview" alt="avatar">
            </div>
            <div v-else class="avatar-placeholder">
              暂无头像
            </div>
            <div class="avatar-actions">
              <el-button size="small" @click="triggerFileInput">选择图片</el-button>
              <el-button v-if="avatarPreview" size="small" type="danger" @click="clearAvatar">清除</el-button>
            </div>
            <input
              ref="fileInputRef"
              type="file"
              accept="image/*"
              class="avatar-input"
              @change="handleFileChange"
            >
          </div>
        </el-form-item>

        <el-form-item label="昵称">
          <el-input
            v-model="form.nickname"
            placeholder="请输入昵称"
            maxlength="50"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="联系人">
          <el-input
            v-model="form.link_person"
            placeholder="请输入联系人姓名"
            maxlength="50"
          />
        </el-form-item>
        <el-form-item label="QQ">
          <el-input
            v-model="form.qq"
            placeholder="请输入QQ号"
            maxlength="20"
          />
        </el-form-item>
      </el-form>

      <div class="actions">
        <el-button
          data-testid="submit-btn"
          type="primary"
          :loading="submitting"
          @click="handleSubmit"
        >保存</el-button>
        <el-button data-testid="cancel-btn" @click="goBack">取消</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-edit-view {
  max-width: 560px;
  margin: 20px auto;
  padding: 20px;
}
.edit-panel {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.phone-text {
  color: #606266;
  font-size: 14px;
}
.avatar-upload {
  display: flex;
  align-items: center;
  gap: 16px;
}
.avatar-preview {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid #ebeef5;
}
.avatar-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.avatar-placeholder {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #909399;
  font-size: 12px;
  border: 1px solid #ebeef5;
}
.avatar-actions {
  display: flex;
  gap: 8px;
}
.avatar-input {
  display: none;
}
.actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}
</style>
