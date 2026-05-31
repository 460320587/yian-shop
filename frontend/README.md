# 怡安印刷商城 - 前端项目

## 技术栈
- **框架**: Vue 3 + TypeScript
- **构建工具**: Vite
- **UI 组件库**: Element Plus
- **状态管理**: Pinia
- **路由**: Vue Router 4
- **HTTP 客户端**: Axios
- **代码规范**: ESLint + Prettier

## 快速开始

```bash
# 安装依赖
npm install

# 启动开发服务器
npm run dev

# 构建生产环境
npm run build

# 预览生产构建
npm run preview

# 代码检查
npm run lint
```

## 项目结构

```
src/
├── api/           # API 接口封装（按模块分文件）
├── assets/        # 静态资源
├── components/    # 公共组件
│   ├── Layout/    # 布局组件
│   ├── Common/    # 通用组件
│   └── Business/  # 业务组件
├── composables/   # 组合式函数
├── router/        # 路由配置
├── stores/        # Pinia 状态管理
├── styles/        # 全局样式
├── types/         # TypeScript 类型定义
├── utils/         # 工具函数
├── views/         # 页面视图
├── App.vue
└── main.ts
```

## 代理配置

开发环境下，`/api` 请求会自动代理到 `http://yashop.test`（见 `vite.config.ts`）。
