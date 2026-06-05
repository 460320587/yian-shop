import { ref, computed } from 'vue'

export interface PaginationOptions {
  page?: number
  pageSize?: number
  total?: number
}

export interface PaginationResult {
  currentPage: ReturnType<typeof ref<number>>
  pageSize: ReturnType<typeof ref<number>>
  total: ReturnType<typeof ref<number>>
  offset: ReturnType<typeof computed<number>>
  handleCurrentChange: (page: number) => void
  handleSizeChange: (size: number) => void
  setTotal: (value: number) => void
  reset: () => void
}

export function usePagination(options: PaginationOptions = {}): PaginationResult {
  const currentPage = ref(options.page ?? 1)
  const pageSize = ref(options.pageSize ?? 10)
  const total = ref(options.total ?? 0)

  const offset = computed(() => (currentPage.value - 1) * pageSize.value)

  function handleCurrentChange(page: number) {
    currentPage.value = page
  }

  function handleSizeChange(size: number) {
    pageSize.value = size
    currentPage.value = 1
  }

  function setTotal(value: number) {
    total.value = value
  }

  function reset() {
    currentPage.value = 1
    pageSize.value = 10
    total.value = 0
  }

  return {
    currentPage,
    pageSize,
    total,
    offset,
    handleCurrentChange,
    handleSizeChange,
    setTotal,
    reset,
  }
}
