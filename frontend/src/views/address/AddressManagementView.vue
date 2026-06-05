<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getAddresses,
  createAddress,
  updateAddress,
  deleteAddress,
  setDefaultAddress,
  type Address,
  type AddressFormData,
} from '@/api/address'

interface FormState {
  id: number | null
  contact_name: string
  contact_phone: string
  province_name: string
  city_name: string
  county_name: string
  detail_address: string
  zip_code: string
  is_default: boolean
  tag: string
}

const addresses = ref<Address[]>([])
const loading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const formRef = ref<any>(null)

const form = ref<FormState>({
  id: null,
  contact_name: '',
  contact_phone: '',
  province_name: '',
  city_name: '',
  county_name: '',
  detail_address: '',
  zip_code: '',
  is_default: false,
  tag: '',
})

const rules = {
  contact_name: [
    { required: true, message: '联系人姓名必填', trigger: 'blur' },
    { min: 2, max: 20, message: '长度在 2 到 20 个字符', trigger: 'blur' },
  ],
  contact_phone: [
    { required: true, message: '联系电话必填', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' },
  ],
  province_name: [
    { required: true, message: '省必填', trigger: 'blur' },
    { min: 2, max: 30, message: '长度在 2 到 30 个字符', trigger: 'blur' },
  ],
  city_name: [
    { required: true, message: '市必填', trigger: 'blur' },
    { min: 2, max: 30, message: '长度在 2 到 30 个字符', trigger: 'blur' },
  ],
  county_name: [
    { required: true, message: '区/县必填', trigger: 'blur' },
    { min: 2, max: 30, message: '长度在 2 到 30 个字符', trigger: 'blur' },
  ],
  detail_address: [
    { required: true, message: '详细地址必填', trigger: 'blur' },
    { min: 5, max: 200, message: '长度在 5 到 200 个字符', trigger: 'blur' },
  ],
  zip_code: [
    { pattern: /^\d{6}$/, message: '邮编格式不正确', trigger: 'blur' },
  ],
  tag: [
    { max: 20, message: '最多 20 个字符', trigger: 'blur' },
  ],
}

async function loadAddresses() {
  loading.value = true
  try {
    const res = await getAddresses({ page: 1, per_page: 50 })
    addresses.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    id: null,
    contact_name: '',
    contact_phone: '',
    province_name: '',
    city_name: '',
    county_name: '',
    detail_address: '',
    zip_code: '',
    is_default: false,
    tag: '',
  }
}

function openDialog(address?: Address) {
  dialogVisible.value = true
  if (address) {
    isEdit.value = true
    form.value = {
      id: address.id,
      contact_name: address.contact_name,
      contact_phone: address.contact_phone,
      province_name: address.province_name,
      city_name: address.city_name,
      county_name: address.county_name,
      detail_address: address.detail_address,
      zip_code: address.zip_code || '',
      is_default: address.is_default,
      tag: address.tag || '',
    }
  } else {
    isEdit.value = false
    resetForm()
  }
}

function closeDialog() {
  dialogVisible.value = false
  resetForm()
}

async function validateForm(): Promise<boolean> {
  if (formRef.value && typeof formRef.value.validate === 'function') {
    try {
      await formRef.value.validate()
      return true
    } catch {
      return false
    }
  }
  // fallback for test environments where el-form is not resolved
  const f = form.value
  if (!f.contact_name || f.contact_name.length < 2 || f.contact_name.length > 20) return false
  if (!f.contact_phone || !/^1[3-9]\d{9}$/.test(f.contact_phone)) return false
  if (!f.province_name || f.province_name.length < 2 || f.province_name.length > 30) return false
  if (!f.city_name || f.city_name.length < 2 || f.city_name.length > 30) return false
  if (!f.county_name || f.county_name.length < 2 || f.county_name.length > 30) return false
  if (!f.detail_address || f.detail_address.length < 5 || f.detail_address.length > 200) return false
  if (f.zip_code && !/^\d{6}$/.test(f.zip_code)) return false
  if (f.tag && f.tag.length > 20) return false
  return true
}

async function submitForm() {
  const valid = await validateForm()
  if (!valid) return

  const payload: AddressFormData = {
    contact_name: form.value.contact_name,
    contact_phone: form.value.contact_phone,
    province_name: form.value.province_name,
    city_name: form.value.city_name,
    county_name: form.value.county_name,
    detail_address: form.value.detail_address,
    zip_code: form.value.zip_code || undefined,
    is_default: form.value.is_default,
    tag: form.value.tag || undefined,
  }

  try {
    if (isEdit.value && form.value.id) {
      await updateAddress(form.value.id, payload)
      ElMessage.success('地址更新成功')
    } else {
      await createAddress(payload)
      ElMessage.success('地址添加成功')
    }
    closeDialog()
    await loadAddresses()
  } catch (e) {
    console.error(e)
  }
}

async function handleDelete(address: Address) {
  try {
    await ElMessageBox.confirm('确定删除该地址吗？', '提示', { type: 'warning' })
    await deleteAddress(address.id)
    ElMessage.success('删除成功')
    await loadAddresses()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

async function handleSetDefault(address: Address) {
  try {
    await setDefaultAddress(address.id)
    ElMessage.success('默认地址设置成功')
    await loadAddresses()
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadAddresses()
})

defineExpose({
  addresses,
  dialogVisible,
  isEdit,
  form,
  openDialog,
  submitForm,
  handleDelete,
  handleSetDefault,
  validateForm,
})
</script>

<template>
  <div class="address-management-view page-container">
    <div class="page-header">
      <h2 class="page-title">收货地址</h2>
      <el-button type="primary" @click="openDialog()">+ 新建地址</el-button>
    </div>

    <div v-loading="loading" class="address-list">
      <el-empty v-if="!loading && addresses.length === 0" description="暂无收货地址" />

      <div
        v-for="address in addresses"
        :key="address.id"
        :class="['address-item', { default: address.is_default }]"
      >
        <div class="address-header">
          <span class="contact-name">{{ address.contact_name }}</span>
          <span class="contact-phone">{{ address.contact_phone }}</span>
          <el-tag v-if="address.is_default" type="danger" size="small">默认</el-tag>
          <el-tag v-if="address.tag" type="info" size="small">{{ address.tag }}</el-tag>
        </div>
        <div class="address-detail">{{ address.full_address }}</div>
        <div class="address-actions">
          <el-button link type="primary" @click="openDialog(address)">编辑</el-button>
          <el-button link type="danger" @click="handleDelete(address)">删除</el-button>
          <el-button
            v-if="!address.is_default"
            link
            type="primary"
            @click="handleSetDefault(address)"
          >
            设为默认
          </el-button>
        </div>
      </div>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑地址' : '新建地址'"
      width="560px"
      @close="resetForm"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="联系人" prop="contact_name">
          <el-input v-model="form.contact_name" placeholder="请输入联系人姓名" maxlength="20" />
        </el-form-item>
        <el-form-item label="手机号" prop="contact_phone">
          <el-input v-model="form.contact_phone" placeholder="请输入手机号" maxlength="11" />
        </el-form-item>
        <el-form-item label="所在地区">
          <div class="region-row">
            <el-form-item prop="province_name" class="region-item">
              <el-input v-model="form.province_name" placeholder="省" />
            </el-form-item>
            <el-form-item prop="city_name" class="region-item">
              <el-input v-model="form.city_name" placeholder="市" />
            </el-form-item>
            <el-form-item prop="county_name" class="region-item">
              <el-input v-model="form.county_name" placeholder="区/县" />
            </el-form-item>
          </div>
        </el-form-item>
        <el-form-item label="详细地址" prop="detail_address">
          <el-input
            v-model="form.detail_address"
            type="textarea"
            :rows="2"
            placeholder="请输入详细地址"
            maxlength="200"
          />
        </el-form-item>
        <el-form-item label="邮编" prop="zip_code">
          <el-input v-model="form.zip_code" placeholder="请输入邮编" maxlength="6" />
        </el-form-item>
        <el-form-item label="标签" prop="tag">
          <el-input v-model="form.tag" placeholder="如：家、公司" maxlength="20" />
        </el-form-item>
        <el-form-item label="默认地址">
          <el-switch v-model="form.is_default" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeDialog">取消</el-button>
        <el-button type="primary" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.address-management-view {
  padding: 20px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.address-list {
  min-height: 200px;
}
.address-item {
  padding: 16px;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
}
.address-item.default {
  background: #fdf5f5;
  border-left: 3px solid #f56c6c;
}
.address-item:hover {
  background: #f5f7fa;
}
.address-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.contact-name {
  font-weight: 600;
  font-size: 15px;
}
.contact-phone {
  color: #606266;
  font-size: 14px;
}
.address-detail {
  color: #303133;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 8px;
}
.address-actions {
  display: flex;
  gap: 12px;
}
.region-row {
  display: flex;
  gap: 12px;
}
.region-item {
  flex: 1;
  margin-bottom: 0;
}
</style>
