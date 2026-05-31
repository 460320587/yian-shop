# 怡安印刷商城 — 前端业务逻辑Hooks与工具函数

> **版本**: v1.0  
> **日期**: 2026-05-30  
> **用途**: 支付状态轮询Hook、文件上传分片逻辑、其他核心工具函数  
> **位置**: `src/composables/` + `src/utils/`

---

## 目录

1. [usePaymentPolling 支付状态轮询Hook](#1-usepaymentpolling-支付状态轮询hook)
2. [useChunkUpload 分片上传Hook](#2-usechunkupload-分片上传hook)
3. [useCountdown 倒计时Hook](#3-usecountdown-倒计时hook)
4. [useWebSocket WebSocket Hook](#4-usewebsocket-websocket-hook)
5. [formatPrice 价格格式化工具](#5-formatprice-价格格式化工具)
6. [debounce/throttle 防抖节流工具](#6-debounce-throttle-防抖节流工具)

---

## 1. usePaymentPolling 支付状态轮询Hook

### 文件路径
`src/composables/usePaymentPolling.ts`

### 功能说明
- 订单提交后主动轮询支付状态，直到支付成功/失败/超时
- 支持手动取消轮询
- 自动退避策略（指数退避），避免高频请求

### 完整代码

```typescript
import { ref, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { checkPaymentStatus } from '@/api/payment';

export interface PaymentPollingOptions {
  orderNo: string;
  paymentNo: string;
  maxRetries?: number;        // 最大重试次数，默认60次(2分钟)
  interval?: number;          // 初始轮询间隔(ms)，默认2000
  backoffMultiplier?: number; // 退避倍数，默认1.5
  maxInterval?: number;       // 最大间隔(ms)，默认10000
  onSuccess?: () => void;
  onFail?: (reason: string) => void;
  onTimeout?: () => void;
}

export interface PaymentPollingResult {
  status: Ref<PaymentPollingStatus>;
  isPolling: ComputedRef<boolean>;
  startPolling: () => void;
  stopPolling: () => void;
}

export type PaymentPollingStatus = 
  | 'idle'       // 空闲
  | 'polling'    // 轮询中
  | 'success'    // 支付成功
  | 'failed'     // 支付失败
  | 'timeout'    // 轮询超时
  | 'cancelled'; // 手动取消

export function usePaymentPolling(options: PaymentPollingOptions): PaymentPollingResult {
  const router = useRouter();
  const status = ref<PaymentPollingStatus>('idle');
  let timer: ReturnType<typeof setTimeout> | null = null;
  let retryCount = 0;

  const {
    orderNo,
    paymentNo,
    maxRetries = 60,
    interval = 2000,
    backoffMultiplier = 1.5,
    maxInterval = 10000,
    onSuccess,
    onFail,
    onTimeout,
  } = options;

  const isPolling = computed(() => status.value === 'polling');

  const doPoll = async (currentInterval: number): Promise<void> => {
    if (status.value !== 'polling') return;

    try {
      const res = await checkPaymentStatus({ orderNo, paymentNo });

      switch (res.data.status) {
        case 'success':
          status.value = 'success';
          ElMessage.success('支付成功！');
          onSuccess?.();
          router.push(`/order/result?orderNo=${orderNo}&status=success`);
          return;

        case 'failed':
          status.value = 'failed';
          ElMessage.error(`支付失败：${res.data.failReason || '未知原因'}`);
          onFail?.(res.data.failReason || '支付失败');
          return;

        case 'cancelled':
          status.value = 'cancelled';
          onFail?.('支付已取消');
          return;

        case 'pending':
        default:
          retryCount++;
          if (retryCount >= maxRetries) {
            status.value = 'timeout';
            ElMessage.warning('支付超时，请刷新页面或联系客服');
            onTimeout?.();
            return;
          }

          // 指数退避
          const nextInterval = Math.min(
            currentInterval * backoffMultiplier,
            maxInterval
          );
          timer = setTimeout(() => doPoll(nextInterval), nextInterval);
          break;
      }
    } catch (error) {
      // 网络错误继续轮询
      retryCount++;
      if (retryCount >= maxRetries) {
        status.value = 'timeout';
        onTimeout?.();
        return;
      }
      const nextInterval = Math.min(
        currentInterval * backoffMultiplier,
        maxInterval
      );
      timer = setTimeout(() => doPoll(nextInterval), nextInterval);
    }
  };

  const startPolling = (): void => {
    if (status.value === 'polling') return;
    status.value = 'polling';
    retryCount = 0;
    doPoll(interval);
  };

  const stopPolling = (): void => {
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }
    if (status.value === 'polling') {
      status.value = 'cancelled';
    }
  };

  // 组件卸载时自动停止
  onUnmounted(() => {
    stopPolling();
  });

  return {
    status,
    isPolling,
    startPolling,
    stopPolling,
  };
}
```

### 使用示例

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { usePaymentPolling } from '@/composables/usePaymentPolling';

const props = defineProps<{
  orderNo: string;
  paymentNo: string;
}>();

const { status, isPolling, startPolling, stopPolling } = usePaymentPolling({
  orderNo: props.orderNo,
  paymentNo: props.paymentNo,
  maxRetries: 60,
  onSuccess: () => {
    // 刷新订单列表
    orderStore.fetchOrders();
  },
});

onMounted(() => {
  startPolling();
});
</script>

<template>
  <div class="payment-polling">
    <el-loading v-if="isPolling" text="正在确认支付结果..." />
    <div v-else-if="status === 'success'" class="success">
      <el-icon><CircleCheck /></el-icon>
      <span>支付成功</span>
    </div>
    <div v-else-if="status === 'failed'" class="failed">
      <el-icon><CircleClose /></el-icon>
      <span>支付失败</span>
      <el-button @click="startPolling">重新查询</el-button>
    </div>
    <div v-else-if="status === 'timeout'" class="timeout">
      <el-icon><Warning /></el-icon>
      <span>查询超时，请稍后查看订单状态</span>
      <el-button @click="$router.push('/orders')">查看订单</el-button>
    </div>
  </div>
</template>
```

---

## 2. useChunkUpload 分片上传Hook

### 文件路径
`src/composables/useChunkUpload.ts`

### 功能说明
- 大文件分片上传（设计文件PDF/CDR/AI等，可能达500MB+）
- 断点续传：记录已上传分片，刷新页面后可续传
- 并发上传：同时上传多个分片
- MD5校验：确保分片完整性
- 自动合并：所有分片上传完成后通知后端合并

### 完整代码

```typescript
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { uploadChunk, initChunkUpload, mergeChunks } from '@/api/upload';
import { calculateMD5 } from '@/utils/crypto';

export interface ChunkUploadOptions {
  file: File;
  chunkSize?: number;       // 分片大小，默认5MB
  concurrency?: number;     // 并发数，默认3
  onProgress?: (percent: number) => void;
  onChunkComplete?: (chunkIndex: number, total: number) => void;
}

export interface ChunkUploadResult {
  uploadId: string;         // 上传任务ID
  progress: Ref<number>;    // 总进度 0-100
  uploading: ComputedRef<boolean>;
  paused: Ref<boolean>;
  error: Ref<string | null>;
  start: () => Promise<string>;  // 返回文件URL
  pause: () => void;
  resume: () => Promise<string>;
  cancel: () => void;
}

export interface ChunkInfo {
  index: number;
  start: number;
  end: number;
  md5: string;
  uploaded: boolean;
}

const STORAGE_KEY = 'chunk_upload_progress';

export function useChunkUpload(options: ChunkUploadOptions): ChunkUploadResult {
  const { file, chunkSize = 5 * 1024 * 1024, concurrency = 3 } = options;

  const progress = ref(0);
  const paused = ref(false);
  const error = ref<string | null>(null);
  const uploadId = ref('');
  const chunks = ref<ChunkInfo[]>([]);
  let abortControllers: AbortController[] = [];

  const uploading = computed(() => progress.value > 0 && progress.value < 100 && !paused.value);

  // 生成或恢复分片信息
  const initChunks = async (): Promise<ChunkInfo[]> => {
    const totalChunks = Math.ceil(file.size / chunkSize);
    const saved = getSavedProgress(file.name, file.size);

    const chunkList: ChunkInfo[] = [];
    for (let i = 0; i < totalChunks; i++) {
      const start = i * chunkSize;
      const end = Math.min(start + chunkSize, file.size);
      const chunkBlob = file.slice(start, end);
      const md5 = saved?.chunks[i] || await calculateMD5(chunkBlob);

      chunkList.push({
        index: i,
        start,
        end,
        md5,
        uploaded: saved?.chunks[i] ? true : false,
      });
    }
    return chunkList;
  };

  // 从localStorage恢复进度
  const getSavedProgress = (name: string, size: number): { uploadId: string; chunks: Record<number, string> } | null => {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      if (!data) return null;
      const saved = JSON.parse(data);
      if (saved.name === name && saved.size === size) {
        return saved;
      }
      return null;
    } catch {
      return null;
    }
  };

  // 保存进度到localStorage
  const saveProgress = (): void => {
    const uploadedChunks: Record<number, string> = {};
    chunks.value.forEach(c => {
      if (c.uploaded) uploadedChunks[c.index] = c.md5;
    });
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      uploadId: uploadId.value,
      name: file.name,
      size: file.size,
      chunks: uploadedChunks,
    }));
  };

  // 清理进度
  const clearProgress = (): void => {
    localStorage.removeItem(STORAGE_KEY);
  };

  // 上传单个分片
  const uploadSingleChunk = async (chunk: ChunkInfo): Promise<void> => {
    if (chunk.uploaded || paused.value) return;

    const controller = new AbortController();
    abortControllers.push(controller);

    try {
      const blob = file.slice(chunk.start, chunk.end);
      const formData = new FormData();
      formData.append('uploadId', uploadId.value);
      formData.append('chunkIndex', String(chunk.index));
      formData.append('chunkMd5', chunk.md5);
      formData.append('file', blob);
      formData.append('totalChunks', String(chunks.value.length));
      formData.append('filename', file.name);
      formData.append('fileSize', String(file.size));

      await uploadChunk(formData, {
        signal: controller.signal,
        onUploadProgress: (evt) => {
          // 单分片进度计入总进度
          const singleProgress = (evt.loaded / evt.total) * (100 / chunks.value.length);
          const completedProgress = chunks.value.filter(c => c.uploaded).length * (100 / chunks.value.length);
          progress.value = Math.min(completedProgress + singleProgress, 99);
          options.onProgress?.(Math.floor(progress.value));
        },
      });

      chunk.uploaded = true;
      saveProgress();
      options.onChunkComplete?.(chunk.index, chunks.value.length);
    } catch (err) {
      if (!controller.signal.aborted) {
        throw err;
      }
    } finally {
      abortControllers = abortControllers.filter(c => c !== controller);
    }
  };

  // 并发上传
  const uploadChunks = async (): Promise<void> => {
    const pendingChunks = chunks.value.filter(c => !c.uploaded);
    const executing: Promise<void>[] = [];

    for (const chunk of pendingChunks) {
      if (paused.value) break;

      const promise = uploadSingleChunk(chunk).catch(err => {
        error.value = `分片${chunk.index}上传失败: ${err.message}`;
        throw err;
      });

      executing.push(promise);

      if (executing.length >= concurrency) {
        await Promise.race(executing);
        executing.splice(executing.findIndex(p => p === promise), 1);
      }
    }

    await Promise.all(executing);
  };

  const doUpload = async (): Promise<string> => {
    error.value = null;
    progress.value = 0;

    // 1. 初始化上传
    const saved = getSavedProgress(file.name, file.size);
    if (saved?.uploadId) {
      uploadId.value = saved.uploadId;
    } else {
      const initRes = await initChunkUpload({
        filename: file.name,
        fileSize: file.size,
        chunkSize,
        fileMd5: await calculateMD5(file),
      });
      uploadId.value = initRes.data.uploadId;
    }

    // 2. 准备分片
    chunks.value = await initChunks();

    // 3. 上传分片
    await uploadChunks();

    if (paused.value) {
      throw new Error('上传已暂停');
    }

    // 4. 合并分片
    const mergeRes = await mergeChunks({
      uploadId: uploadId.value,
      filename: file.name,
      totalChunks: chunks.value.length,
      fileSize: file.size,
    });

    progress.value = 100;
    clearProgress();
    return mergeRes.data.fileUrl;
  };

  const start = (): Promise<string> => {
    paused.value = false;
    return doUpload();
  };

  const pause = (): void => {
    paused.value = true;
    abortControllers.forEach(c => c.abort());
    abortControllers = [];
  };

  const resume = (): Promise<string> => {
    paused.value = false;
    return doUpload();
  };

  const cancel = (): void => {
    pause();
    progress.value = 0;
    clearProgress();
    // 通知后端取消
    // await cancelChunkUpload(uploadId.value);
  };

  return {
    uploadId: uploadId.value,
    progress,
    uploading,
    paused,
    error,
    start,
    pause,
    resume,
    cancel,
  };
}
```

### 使用示例

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useChunkUpload } from '@/composables/useChunkUpload';

const file = ref<File | null>(null);
const uploader = ref<ReturnType<typeof useChunkUpload> | null>(null);

const handleFileChange = (f: File) => {
  file.value = f;
  uploader.value = useChunkUpload({
    file: f,
    chunkSize: 5 * 1024 * 1024, // 5MB
    concurrency: 3,
    onProgress: (p) => console.log(`上传进度: ${p}%`),
    onChunkComplete: (idx, total) => console.log(`分片 ${idx+1}/${total} 完成`),
  });
};

const handleUpload = async () => {
  if (!uploader.value) return;
  try {
    const fileUrl = await uploader.value.start();
    ElMessage.success('上传成功');
    formData.designFile = fileUrl;
  } catch (err) {
    ElMessage.error(err.message);
  }
};
</script>

<template>
  <div class="chunk-upload">
    <el-upload :on-change="handleFileChange" :auto-upload="false">
      <el-button>选择文件</el-button>
    </el-upload>
    <el-progress v-if="uploader" :percentage="uploader.progress.value" />
    <el-button v-if="uploader?.uploading" @click="uploader.pause()">暂停</el-button>
    <el-button v-if="uploader?.paused" @click="uploader.resume()">继续</el-button>
    <el-button type="primary" @click="handleUpload">上传</el-button>
  </div>
</template>
```

---

## 3. useCountdown 倒计时Hook

### 文件路径
`src/composables/useCountdown.ts`

### 完整代码

```typescript
import { ref, computed, onUnmounted } from 'vue';

export interface CountdownOptions {
  seconds: number;
  interval?: number;      // 更新间隔(ms)，默认1000
  onComplete?: () => void;
  onTick?: (remaining: number) => void;
}

export function useCountdown(options: CountdownOptions) {
  const { seconds, interval = 1000, onComplete, onTick } = options;
  const remaining = ref(seconds);
  let timer: ReturnType<typeof setInterval> | null = null;

  const isRunning = computed(() => timer !== null);
  const formatted = computed(() => {
    const m = Math.floor(remaining.value / 60);
    const s = remaining.value % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  });

  const start = (): void => {
    if (timer) return;
    remaining.value = seconds;
    timer = setInterval(() => {
      remaining.value--;
      onTick?.(remaining.value);
      if (remaining.value <= 0) {
        stop();
        onComplete?.();
      }
    }, interval);
  };

  const stop = (): void => {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  };

  const reset = (): void => {
    stop();
    remaining.value = seconds;
  };

  onUnmounted(stop);

  return {
    remaining,
    formatted,
    isRunning,
    start,
    stop,
    reset,
  };
}
```

---

## 4. useWebSocket WebSocket Hook

### 文件路径
`src/composables/useWebSocket.ts`

### 完整代码

```typescript
import { ref, onUnmounted } from 'vue';

export interface WebSocketOptions {
  url: string;
  reconnectInterval?: number;   // 重连间隔(ms)，默认3000
  maxReconnectAttempts?: number; // 最大重连次数，默认5
  heartbeatInterval?: number;    // 心跳间隔(ms)，默认30000
  onMessage?: (data: any) => void;
  onOpen?: () => void;
  onClose?: () => void;
  onError?: (error: Event) => void;
}

export function useWebSocket(options: WebSocketOptions) {
  const { 
    url, 
    reconnectInterval = 3000, 
    maxReconnectAttempts = 5,
    heartbeatInterval = 30000,
  } = options;

  const isConnected = ref(false);
  const reconnectCount = ref(0);
  let ws: WebSocket | null = null;
  let heartbeatTimer: ReturnType<typeof setInterval> | null = null;
  let reconnectTimer: ReturnType<typeof setTimeout> | null = null;

  const connect = (): void => {
    if (ws?.readyState === WebSocket.OPEN) return;

    ws = new WebSocket(url);

    ws.onopen = () => {
      isConnected.value = true;
      reconnectCount.value = 0;
      startHeartbeat();
      options.onOpen?.();
    };

    ws.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        // 处理心跳响应
        if (data.type === 'pong') return;
        options.onMessage?.(data);
      } catch {
        options.onMessage?.(event.data);
      }
    };

    ws.onclose = () => {
      isConnected.value = false;
      stopHeartbeat();
      options.onClose?.();
      attemptReconnect();
    };

    ws.onerror = (error) => {
      options.onError?.(error);
    };
  };

  const startHeartbeat = (): void => {
    heartbeatTimer = setInterval(() => {
      if (ws?.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'ping' }));
      }
    }, heartbeatInterval);
  };

  const stopHeartbeat = (): void => {
    if (heartbeatTimer) {
      clearInterval(heartbeatTimer);
      heartbeatTimer = null;
    }
  };

  const attemptReconnect = (): void => {
    if (reconnectCount.value >= maxReconnectAttempts) return;
    reconnectCount.value++;
    reconnectTimer = setTimeout(() => {
      connect();
    }, reconnectInterval * reconnectCount.value); // 递增重连间隔
  };

  const send = (data: any): boolean => {
    if (ws?.readyState === WebSocket.OPEN) {
      ws.send(typeof data === 'string' ? data : JSON.stringify(data));
      return true;
    }
    return false;
  };

  const disconnect = (): void => {
    if (reconnectTimer) {
      clearTimeout(reconnectTimer);
      reconnectTimer = null;
    }
    stopHeartbeat();
    if (ws) {
      ws.onclose = null; // 防止触发重连
      ws.close();
      ws = null;
    }
    isConnected.value = false;
  };

  onUnmounted(disconnect);

  return {
    isConnected,
    reconnectCount,
    connect,
    disconnect,
    send,
  };
}
```

---

## 5. formatPrice 价格格式化工具

### 文件路径
`src/utils/format.ts`

```typescript
/**
 * 价格格式化（分 → 元）
 * @param cents 金额（分）
 * @param decimals 小数位数，默认2
 * @param prefix 前缀，默认'¥'
 */
export function formatPrice(cents: number | string, decimals = 2, prefix = '¥'): string {
  const num = typeof cents === 'string' ? parseInt(cents, 10) : cents;
  if (isNaN(num)) return `${prefix}0.00`;
  const yuan = (num / 100).toFixed(decimals);
  return `${prefix}${yuan}`;
}

/**
 * 数字千分位格式化
 */
export function formatNumber(num: number): string {
  return num.toLocaleString('zh-CN');
}

/**
 * 文件大小格式化
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * 日期格式化
 */
export function formatDate(date: Date | string, format = 'YYYY-MM-DD HH:mm:ss'): string {
  const d = typeof date === 'string' ? new Date(date) : date;
  const pad = (n: number) => String(n).padStart(2, '0');
  const map: Record<string, string> = {
    'YYYY': String(d.getFullYear()),
    'MM': pad(d.getMonth() + 1),
    'DD': pad(d.getDate()),
    'HH': pad(d.getHours()),
    'mm': pad(d.getMinutes()),
    'ss': pad(d.getSeconds()),
  };
  return format.replace(/YYYY|MM|DD|HH|mm|ss/g, (match) => map[match]);
}
```

---

## 6. debounce/throttle 防抖节流工具

### 文件路径
`src/utils/lodash-like.ts`

```typescript
/**
 * 防抖
 */
export function debounce<T extends (...args: any[]) => void>(
  fn: T,
  delay: number,
  immediate = false
): (...args: Parameters<T>) => void {
  let timer: ReturnType<typeof setTimeout> | null = null;
  return function (this: any, ...args: Parameters<T>) {
    const callNow = immediate && !timer;
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
      timer = null;
      if (!immediate) fn.apply(this, args);
    }, delay);
    if (callNow) fn.apply(this, args);
  };
}

/**
 * 节流
 */
export function throttle<T extends (...args: any[]) => void>(
  fn: T,
  limit: number,
  trailing = true
): (...args: Parameters<T>) => void {
  let inThrottle = false;
  let lastArgs: Parameters<T> | null = null;
  return function (this: any, ...args: Parameters<T>) {
    if (!inThrottle) {
      fn.apply(this, args);
      inThrottle = true;
      setTimeout(() => {
        inThrottle = false;
        if (trailing && lastArgs) {
          fn.apply(this, lastArgs);
          lastArgs = null;
        }
      }, limit);
    } else if (trailing) {
      lastArgs = args;
    }
  };
}

/**
 * 深拷贝
 */
export function deepClone<T>(obj: T): T {
  if (obj === null || typeof obj !== 'object') return obj;
  if (obj instanceof Date) return new Date(obj.getTime()) as unknown as T;
  if (obj instanceof Array) return obj.map(item => deepClone(item)) as unknown as T;
  if (typeof obj === 'object') {
    const cloned = {} as T;
    for (const key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        cloned[key] = deepClone(obj[key]);
      }
    }
    return cloned;
  }
  return obj;
}
```

---

*前端业务逻辑Hooks与工具函数已全部定义完成，开发可直接复制到对应目录使用。支付轮询Hook覆盖全状态流转，分片上传Hook支持断点续传+并发+MD5校验，满足大文件设计稿上传需求。*
