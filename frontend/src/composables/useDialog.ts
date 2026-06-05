import { ref } from 'vue'

export interface UseDialogResult {
  visible: ReturnType<typeof ref<boolean>>
  confirmLoading: ReturnType<typeof ref<boolean>>
  open: () => void
  close: () => void
  toggle: () => void
  startLoading: () => void
  stopLoading: () => void
}

export function useDialog(): UseDialogResult {
  const visible = ref(false)
  const confirmLoading = ref(false)

  function open() {
    visible.value = true
    confirmLoading.value = false
  }

  function close() {
    visible.value = false
    confirmLoading.value = false
  }

  function toggle() {
    visible.value = !visible.value
    if (!visible.value) {
      confirmLoading.value = false
    }
  }

  function startLoading() {
    confirmLoading.value = true
  }

  function stopLoading() {
    confirmLoading.value = false
  }

  return {
    visible,
    confirmLoading,
    open,
    close,
    toggle,
    startLoading,
    stopLoading,
  }
}
