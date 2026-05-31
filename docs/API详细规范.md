# 怡安印刷商城 — API详细规范（API Detailed Specification）

> **版本**: v1.0  
> **日期**: 2026-05-30  
> **规范**: OpenAPI 3.0  
> **内容**: 20个核心API的请求/响应DTO定义，可直接用于前后端开发联调  
> **基础URL**: `https://api.yian.com/api/v1`  
> **认证方式**: Bearer Token (`Authorization: Bearer {access_token}`)  
> **通用响应头**: `Content-Type: application/json; charset=utf-8`  
> **幂等Key**: 非幂等接口（POST/PUT/DELETE）需携带 `Idempotency-Key: {UUIDv7}`  

---

## 目录

1. [通用规范](#1-通用规范)
2. [用户认证](#2-用户认证-auth)
3. [商品系统](#3-商品系统-products)
4. [购物车](#4-购物车-cart)
5. [订单系统](#5-订单系统-orders)
6. [支付系统](#6-支付系统-payments)
7. [地址管理](#7-地址管理-addresses)
8. [通知系统](#8-通知系统-notifications)

---

## 1. 通用规范

### 1.1 通用响应结构

```json
{
  "code": "0000",
  "message": "success",
  "data": { ... },
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-05-30T12:00:00+08:00"
  }
}
```

### 1.2 通用分页响应

```json
{
  "code": "0000",
  "message": "success",
  "data": [ ... ],
  "meta": {
    "pagination": {
      "total": 156,
      "current_page": 1,
      "per_page": 20,
      "last_page": 8
    }
  }
}
```

### 1.3 通用错误响应

```json
{
  "code": "4001",
  "message": "订单不存在",
  "data": null,
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-05-30T12:00:00+08:00"
  }
}
```

### 1.4 HTTP状态码

| 状态码 | 含义 | 场景 |
|:------:|------|------|
| 200 | 成功 | GET/PUT成功 |
| 201 | 创建成功 | POST创建资源成功 |
| 204 | 无内容 | DELETE成功 |
| 400 | 请求参数错误 | 字段校验失败/缺少必填字段 |
| 401 | 未认证 | Token缺失/过期/无效 |
| 403 | 无权限 | 角色权限不足 |
| 404 | 资源不存在 | 订单/商品不存在 |
| 409 | 资源冲突 | 库存不足/重复提交/状态不允许 |
| 422 | 业务规则校验失败 | 金额不匹配/优惠券不可用 |
| 429 | 请求过于频繁 | 限流触发 |
| 500 | 服务器内部错误 | 系统异常 |

### 1.5 Domain业务错误码（附录D速查）

| 领域 | 码段 | 常见码 |
|------|------|--------|
| 通用 | 0000-0999 | 0000: 成功 / 0001: 参数错误 / 0002: 系统繁忙 |
| 用户 | 1000-1999 | 1001: 用户不存在 / 1002: 密码错误 / 1003: 账号锁定 |
| 商品 | 2000-2999 | 2001: 商品不存在 / 2002: 商品已下架 |
| 购物车 | 3000-3999 | 3001: 购物车为空 / 3002: 商品库存不足 |
| 订单 | 4000-4999 | 4001: 订单不存在 / 4002: 状态不允许操作 / 4003: 库存预占失败 |
| 支付 | 5000-5999 | 5001: 支付超时 / 5002: 金额不匹配 / 5003: 退款金额超出 / 5004: 渠道不可用 |
| 物流 | 6000-6999 | 6001: 地址不支持配送 / 6002: 物流单号不存在 |
| 通知 | 7000-7999 | 7001: 通知不存在 |
| 营销 | 8000-8999 | 8001: 优惠券不存在 / 8002: 优惠券已过期 / 8003: 不满足使用条件 |
| 系统 | 9000-9999 | 9001: 文件上传失败 / 9002: 导出任务创建失败 |

---

## 2. 用户认证 (Auth)

---

### API-001: 用户注册

**POST** `/auth/register`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `phone` | string | ✅ | 手机号 | 大陆手机号，正则：`^1[3-9]\d{9}$` |
| `sms_code` | string | ✅ | 短信验证码 | 6位数字 |
| `password` | string | ✅ | 密码 | 8-32位，含大小写字母+数字+特殊字符至少2种 |
| `password_confirmation` | string | ✅ | 确认密码 | 必须与password一致 |
| `user_type` | int | ✅ | 用户类型 | 1:个人 / 2:企业 |
| `company_name` | string | 条件 | 企业名称 | user_type=2时必填，2-100字符 |
| `credit_code` | string | 条件 | 统一社会信用代码 | user_type=2时必填，18位，含校验位 |
| `invitation_code` | string | ❌ | 邀请码 | 6位字母数字，有则校验有效性 |
| `agreement_version` | string | ✅ | 用户协议版本号 | 如 "v2026.05" |

#### 请求示例

```json
{
  "phone": "13800138000",
  "sms_code": "123456",
  "password": "Yian@2026",
  "password_confirmation": "Yian@2026",
  "user_type": 2,
  "company_name": "北京怡安印刷科技有限公司",
  "credit_code": "91110108MA00XXXX0X",
  "agreement_version": "v2026.05"
}
```

#### 成功响应 (201)

```json
{
  "code": "0000",
  "message": "注册成功",
  "data": {
    "user": {
      "id": 10001,
      "phone": "138****8000",
      "user_type": 2,
      "company_name": "北京怡安印刷科技有限公司",
      "company_auth_status": 0,
      "vip_level": 0,
      "created_at": "2026-05-30T12:00:00+08:00"
    },
    "tokens": {
      "access_token": "eyJhbGciOiJIUzI1NiIs...",
      "refresh_token": "eyJhbGciOiJIUzI1NiIs...",
      "expires_in": 7200,
      "token_type": "Bearer"
    }
  },
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-05-30T12:00:00+08:00"
  }
}
```

#### 失败响应示例

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 400 | 0001 | 手机号格式不正确 | phone不匹配正则 |
| 400 | 0001 | 密码强度不足 | 密码复杂度校验失败 |
| 409 | 1004 | 手机号已被注册 | phone已存在于users表 |
| 422 | 1005 | 短信验证码错误或已过期 | sms_code校验失败或Redis中已过期 |
| 422 | 0001 | 企业名称与信用代码不匹配 | credit_code校验失败 |
| 422 | 1006 | 邀请码无效 | invitation_code不存在或已过期 |

#### 幂等性
- `Idempotency-Key` 必填，同一Key 24h内重复注册返回首次结果（不重复创建用户）

#### 安全限制
- 同一手机号 1分钟内最多发送3次短信验证码
- 同一IP 1小时内最多注册5个账号（风控拦截）

---

### API-002: 用户登录

**POST** `/auth/login`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `phone` | string | ✅ | 手机号 | 大陆手机号 |
| `password` | string | 条件 | 密码 | 8-32位，password和sms_code二选一 |
| `sms_code` | string | 条件 | 短信验证码 | 6位数字，password和sms_code二选一 |
| `device_id` | string | ✅ | 设备指纹 | UUID格式，用于设备管理和安全风控 |
| `device_name` | string | ❌ | 设备名称 | 如 "iPhone 15 Pro" |
| `platform` | string | ❌ | 平台 | ios / android / web / wxh5 |

#### 请求示例

```json
{
  "phone": "13800138000",
  "password": "Yian@2026",
  "device_id": "550e8400-e29b-41d4-a716-446655440000",
  "device_name": "Chrome 126 / Windows 11",
  "platform": "web"
}
```

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "登录成功",
  "data": {
    "user": {
      "id": 10001,
      "phone": "138****8000",
      "user_type": 2,
      "company_name": "北京怡安印刷科技有限公司",
      "company_auth_status": 1,
      "vip_level": 3,
      "vip_name": "金牌会员",
      "avatar": "https://cdn.yian.com/avatars/default.png",
      "notification_unread_count": 5
    },
    "tokens": {
      "access_token": "eyJhbGciOiJIUzI1NiIs...",
      "refresh_token": "eyJhbGciOiJIUzI1NiIs...",
      "expires_in": 7200,
      "token_type": "Bearer"
    }
  },
  "meta": {
    "request_id": "req_def456",
    "timestamp": "2026-05-30T12:00:00+08:00"
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 401 | 1002 | 手机号或密码错误 | 密码不匹配 |
| 401 | 1003 | 账号已被锁定 | 连续5次密码错误，锁定1小时 |
| 401 | 1005 | 短信验证码错误 | sms_code校验失败 |
| 403 | 1007 | 设备异常，请验证身份 | 新设备+风控触发，需短信验证 |
| 429 | 0002 | 登录过于频繁 | 同一IP 10分钟内>20次登录尝试 |

#### 并发登录控制
- 同一账号最多5台设备同时在线
- 第6台登录时，自动踢掉最早登录的设备（返回被踢通知）

---

### API-003: Token刷新

**POST** `/auth/refresh`

#### 请求参数 (Header)

| 字段 | 说明 |
|------|------|
| `X-Refresh-Token` | Refresh Token |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIs...",
    "refresh_token": "eyJhbGciOiJIUzI1NiIs...",
    "expires_in": 7200,
    "token_type": "Bearer"
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 401 | 1008 | Refresh Token已过期或无效 | RT过期/被篡改/已被轮换作废 |

#### 安全机制
- Refresh Token Rotation：刷新后旧RT立即作废，新RT有效期7天

---

### API-004: 用户登出

**POST** `/auth/logout`

#### 请求参数
- Header: `Authorization: Bearer {access_token}`
- Body: 可选 `all_devices` (bool, 是否登出所有设备，默认false)

#### 成功响应 (204)
- 无响应体

---

### API-005: 获取当前用户

**GET** `/auth/me`

#### 请求参数
- Header: `Authorization: Bearer {access_token}`

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "id": 10001,
    "phone": "138****8000",
    "user_type": 2,
    "company_name": "北京怡安印刷科技有限公司",
    "company_auth_status": 1,
    "credit_code": "91110108MA00XXXX0X",
    "vip_level": 3,
    "vip_name": "金牌会员",
    "vip_discount": 0.92,
    "points": 2580,
    "balance": 5000.00,
    "avatar": "https://cdn.yian.com/avatars/default.png",
    "notification_unread_count": 5,
    "created_at": "2026-05-30T12:00:00+08:00"
  }
}
```

---

### API-005-A: 后台登录

**POST** `/admin/auth/login`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `username` | string | ✅ | 用户名/工号 | 3-20字符，字母数字下划线 |
| `password` | string | ✅ | 密码 | — |
| `captcha` | string | ✅ | 图形验证码 | 4位字母数字 |
| `captcha_key` | string | ✅ | 验证码标识 | — |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "登录成功",
  "data": {
    "admin": {
      "id": 100,
      "username": "admin001",
      "real_name": "管理员",
      "role": "super_admin",
      "role_name": "超级管理员",
      "permissions": ["*"]
    },
    "token": {
      "access_token": "eyJhbGciOiJIUzI1NiIs...",
      "expires_in": 7200
    }
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 401 | 1002 | 用户名或密码错误 | 认证失败 |
| 401 | 1005 | 验证码错误 | captcha校验失败 |
| 403 | 1009 | 账号已被禁用 | admin.status = disabled |
| 429 | 0002 | 登录过于频繁 | 同一IP 10min内>10次 |

---

## 3. 商品系统 (Products)

---

### API-006: 商品列表

**GET** `/products`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 | 校验规则 |
|------|------|:----:|------|:------:|---------|
| `category_id` | int | ❌ | 分类ID | — | 正整数 |
| `keyword` | string | ❌ | 搜索关键词 | — | 1-50字符 |
| `min_price` | decimal | ❌ | 最低价格 | — | ≥0 |
| `max_price` | decimal | ❌ | 最高价格 | — | ≥min_price |
| `sort` | string | ❌ | 排序方式 | `default` | default/price_asc/price_desc/sales_desc/newest |
| `page` | int | ❌ | 页码 | 1 | ≥1 |
| `per_page` | int | ❌ | 每页数量 | 20 | 1-100 |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "id": 101,
      "name": "A4企业宣传册",
      "subtitle": "157g铜版纸 | 四色印刷 | 胶装",
      "thumbnail": "https://cdn.yian.com/products/101/thumb_600.webp",
      "min_price": 2.50,
      "max_price": 15.00,
      "sales_count": 12580,
      "rating": 4.8,
      "rating_count": 326,
      "is_new": false,
      "tags": ["热销", "企业首选"],
      "category": {
        "id": 10,
        "name": "宣传册"
      }
    }
  ],
  "meta": {
    "pagination": {
      "total": 156,
      "current_page": 1,
      "per_page": 20,
      "last_page": 8
    },
    "request_id": "req_ghi789"
  }
}
```

---

### API-007: 商品详情

**GET** `/products/{id}`

#### 请求参数 (Path)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `id` | int | ✅ | 商品ID |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "id": 101,
    "name": "A4企业宣传册",
    "subtitle": "157g铜版纸 | 四色印刷 | 胶装",
    "description": "<p>高品质企业宣传册印刷...</p>",
    "images": [
      "https://cdn.yian.com/products/101/img_1_1200.webp",
      "https://cdn.yian.com/products/101/img_2_1200.webp"
    ],
    "category": {
      "id": 10,
      "name": "宣传册",
      "parent_id": 1
    },
    "pricing_params": {
      "base_price": 2.50,
      "unit": "本",
      "price_tiers": [
        {"min_qty": 100, "price": 2.50},
        {"min_qty": 500, "price": 2.00},
        {"min_qty": 1000, "price": 1.60},
        {"min_qty": 5000, "price": 1.20}
      ],
      "paper_options": [
        {"id": 1, "name": "128g铜版纸", "price_factor": 0.85},
        {"id": 2, "name": "157g铜版纸", "price_factor": 1.00},
        {"id": 3, "name": "200g铜版纸", "price_factor": 1.20}
      ],
      "color_options": [
        {"id": 1, "name": "单色", "price_factor": 0.35},
        {"id": 2, "name": "四色", "price_factor": 1.00}
      ],
      "process_options": [
        {"id": 1, "name": "覆膜", "price": 0.60, "unit": "㎡"},
        {"id": 2, "name": "烫金", "price": 2.00, "unit": "㎡", "plate_fee": 200}
      ]
    },
    "specifications": {
      "size": "210mm × 297mm (A4)",
      "bleed": "3mm",
      "resolution": "300dpi",
      "color_mode": "CMYK",
      "file_format": "PDF/X-4"
    },
    "sales_count": 12580,
    "rating": 4.8,
    "rating_count": 326,
    "faq": [
      {"question": "多久可以发货？", "answer": "确认文件后3-5个工作日"},
      {"question": "支持哪些付款方式？", "answer": "微信/支付宝/余额/对公转账"}
    ]
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 404 | 2001 | 商品不存在 | id无效或商品已删除 |
| 404 | 2002 | 商品已下架 | status ≠ 上架中 |

---

### API-008: 商品分类列表

**GET** `/categories`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `parent_id` | int | ❌ | 父分类ID | 0（顶级分类） |
| `depth` | int | ❌ | 递归深度 | 2 |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "id": 1,
      "name": "商务印刷",
      "icon": "https://cdn.yian.com/categories/business.png",
      "children": [
        {"id": 10, "name": "宣传册", "sort": 1},
        {"id": 11, "name": "名片", "sort": 2},
        {"id": 12, "name": "海报", "sort": 3}
      ]
    },
    {
      "id": 2,
      "name": "包装印刷",
      "icon": "https://cdn.yian.com/categories/packaging.png",
      "children": [
        {"id": 20, "name": "包装盒", "sort": 1},
        {"id": 21, "name": "不干胶标签", "sort": 2}
      ]
    }
  ]
}
```

---

### API-009: 实时计价

**POST** `/products/{id}/pricing`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `quantity` | int | ✅ | 印刷数量 | ≥1，≤100000 |
| `paper_id` | int | ✅ | 纸张选项ID | 存在于商品pricing_params.paper_options |
| `color_id` | int | ✅ | 色数选项ID | 存在于商品pricing_params.color_options |
| `process_ids` | int[] | ❌ | 后道工艺ID数组 | 每项存在于商品pricing_params.process_options |
| `pages` | int | 条件 | 页数 | 画册/书籍类必填，≥4且为4的倍数 |
| `size_id` | int | ❌ | 尺寸选项ID | — |
| `urgent` | bool | ❌ | 是否加急 | 默认false |
| `coupon_code` | string | ❌ | 优惠券码 | 如有则校验有效性 |
| `customer_id` | int | ❌ | 客户ID（登录后传入，用于VIP折扣） | 正整数 |

#### 请求示例

```json
{
  "quantity": 1000,
  "paper_id": 2,
  "color_id": 2,
  "process_ids": [1],
  "pages": 48,
  "urgent": false,
  "coupon_code": "SUMMER2026"
}
```

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "product_id": 101,
    "quantity": 1000,
    "breakdown": {
      "base_amount": 3840.00,
      "material_adjustment": 0.00,
      "process_amount": 302.40,
      "quantity_discount": -768.00,
      "vip_discount": -307.20,
      "coupon_discount": -200.00,
      "urgent_fee": 0.00,
      "packaging_fee": 100.00,
      "freight_estimate": 80.00
    },
    "total_amount": 3047.20,
    "floor_applied": false,
    "pricing_version": "v2026.05.30",
    "valid_until": "2026-05-30T12:05:00+08:00"
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 404 | 2001 | 商品不存在 | id无效 |
| 422 | 2003 | 印刷数量超出范围 | quantity < 1 或 > 100000 |
| 422 | 2004 | 无效的纸张选项 | paper_id不在选项中 |
| 422 | 8001 | 优惠券不存在 | coupon_code无效 |
| 422 | 8003 | 优惠券不满足使用条件 | 最低金额/品类限制不满足 |

---

## 4. 购物车 (Cart)

---

### API-010: 获取购物车

**GET** `/cart`

#### 请求参数
- Header: `Authorization: Bearer {access_token}`

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "items": [
      {
        "id": 5001,
        "product_id": 101,
        "product_name": "A4企业宣传册",
        "thumbnail": "https://cdn.yian.com/products/101/thumb_200.webp",
        "quantity": 1000,
        "unit_price": 2.50,
        "subtotal": 2500.00,
        "selected": true,
        "pricing_snapshot": {
          "paper_id": 2,
          "paper_name": "157g铜版纸",
          "color_id": 2,
          "color_name": "四色",
          "process_ids": [1],
          "pages": 48
        },
        "is_valid": true,
        "invalid_reason": null,
        "added_at": "2026-05-30T10:00:00+08:00"
      }
    ],
    "summary": {
      "total_count": 3,
      "selected_count": 2,
      "selected_subtotal": 5047.20,
      "total_weight": 12500,
      "currency": "CNY"
    }
  }
}
```

---

### API-011: 添加商品到购物车

**POST** `/cart/items`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `product_id` | int | ✅ | 商品ID | 正整数 |
| `quantity` | int | ✅ | 印刷数量 | ≥1，≤100000 |
| `paper_id` | int | ✅ | 纸张选项ID | — |
| `color_id` | int | ✅ | 色数选项ID | — |
| `process_ids` | int[] | ❌ | 后道工艺ID数组 | — |
| `pages` | int | 条件 | 页数 | 画册类必填，4的倍数 |
| `selected` | bool | ❌ | 是否选中 | 默认true |

#### 成功响应 (201)

```json
{
  "code": "0000",
  "message": "已加入购物车",
  "data": {
    "cart_item_id": 5002,
    "cart_summary": {
      "total_count": 4,
      "selected_subtotal": 7547.20
    }
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 404 | 2001 | 商品不存在或已下架 | product_id无效 |
| 409 | 3003 | 商品库存不足 | 超出可售数量 |
| 422 | 0001 | 参数校验失败 | pages不是4的倍数等 |

#### 幂等性
- `Idempotency-Key` 必填，同一Key重复添加不重复创建，更新数量

---

### API-012: 更新购物车项

**PUT** `/cart/items/{id}`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `quantity` | int | ❌ | 新数量 |
| `selected` | bool | ❌ | 是否选中 |

---

### API-013: 删除购物车项

**DELETE** `/cart/items/{id}`

#### 成功响应 (204)

---

### API-014: 清空购物车

**DELETE** `/cart`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `only_invalid` | bool | ❌ | 仅清除失效商品 | false |

---

## 5. 订单系统 (Orders)

---

### API-015: 创建订单

**POST** `/orders`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `cart_item_ids` | int[] | 条件 | 购物车项ID数组 | cart_item_ids和custom_items二选一 |
| `custom_items` | array | 条件 | 自定义订单项（非购物车直接下单） | 见下表 |
| `address_id` | int | ✅ | 收货地址ID | 正整数，需属于当前用户 |
| `invoice_type` | int | ✅ | 发票类型 | 0:不开票 / 1:电子普票 / 2:电子专票 |
| `invoice_title` | string | 条件 | 发票抬头 | invoice_type≠0时必填 |
| `invoice_tax_no` | string | 条件 | 发票税号 | invoice_type=2时必填 |
| `invoice_email` | string | 条件 | 发票接收邮箱 | invoice_type≠0时必填，邮箱格式 |
| `remark` | string | ❌ | 订单备注 | 0-500字符 |
| `coupon_code` | string | ❌ | 优惠券码 | — |
| `use_balance` | decimal | ❌ | 使用余额支付金额 | ≥0，≤用户余额 |
| `delivery_date` | string | ❌ | 期望交付日期 | 日期格式YYYY-MM-DD，必须≥T+3 |

**custom_items结构**:

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `product_id` | int | ✅ | 商品ID |
| `quantity` | int | ✅ | 数量 |
| `paper_id` | int | ✅ | 纸张ID |
| `color_id` | int | ✅ | 色数ID |
| `process_ids` | int[] | ❌ | 工艺ID数组 |
| `pages` | int | 条件 | 页数 |
| `file_url` | string | 条件 | 印前文件URL | 部分商品必填 |

#### 请求示例

```json
{
  "cart_item_ids": [5001, 5002],
  "address_id": 2001,
  "invoice_type": 1,
  "invoice_title": "北京怡安印刷科技有限公司",
  "invoice_tax_no": "91110108MA00XXXX0X",
  "invoice_email": "finance@yian.com",
  "remark": "请在封底加印二维码",
  "use_balance": 0,
  "delivery_date": "2026-06-05"
}
```

#### 成功响应 (201)

```json
{
  "code": "0000",
  "message": "订单创建成功",
  "data": {
    "order": {
      "order_no": "Y2026053000012345",
      "parent_order_no": null,
      "status": "pending_payment",
      "customer_status": "待付款",
      "created_at": "2026-05-30T12:00:00+08:00",
      "expire_at": "2026-05-30T12:05:00+08:00",
      "items": [
        {
          "id": 8001,
          "product_id": 101,
          "product_name": "A4企业宣传册",
          "quantity": 1000,
          "unit_price": 2.50,
          "subtotal": 2500.00,
          "pricing_snapshot": { ... }
        }
      ],
      "address": {
        "contact_name": "张三",
        "phone": "138****8000",
        "full_address": "北京市朝阳区xxx路xxx号"
      },
      "amounts": {
        "subtotal": 5047.20,
        "freight": 80.00,
        "discount": -507.20,
        "points_deduction": 0,
        "balance_deduction": 0,
        "tax": 0,
        "total": 4620.00
      },
      "invoice": {
        "type": 1,
        "title": "北京怡安印刷科技有限公司",
        "tax_no": "91110108MA00XXXX0X",
        "email": "finance@yian.com",
        "status": "pending"
      }
    },
    "payment": {
      "payment_id": 9001,
      "amount": 4620.00,
      "expire_at": "2026-05-30T12:05:00+08:00"
    }
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 400 | 3001 | 购物车为空 | cart_item_ids为空或全部无效 |
| 404 | 6001 | 地址不存在 | address_id无效或不属于当前用户 |
| 409 | 4003 | 库存预占失败 | Redis Lua扣减失败 |
| 409 | 4005 | 商品信息已变更 | 购物车项创建后商品/价格变更 |
| 422 | 8003 | 优惠券不可用 | 最低金额/品类不满足 |
| 422 | 0001 | 期望交付日期过近 | delivery_date < T+3 |

#### 幂等性
- `Idempotency-Key` 必填，同一Key 24h内重复请求返回首次创建的订单

#### 金额校验公式
```
total = subtotal + freight - discount - points_deduction - balance_deduction + tax
(校验: |total - (subtotal + freight - discount - points_deduction - balance_deduction + tax)| < 0.01)
```

---

### API-016: 订单列表

**GET** `/orders`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 | 校验规则 |
|------|------|:----:|------|:------:|---------|
| `status` | string | ❌ | 状态筛选 | all | all/pending_payment/paid/producing/shipped/completed/cancelled/after_sale |
| `keyword` | string | ❌ | 订单号/商品名关键词 | — | 1-30字符 |
| `start_date` | string | ❌ | 起始日期 | — | YYYY-MM-DD |
| `end_date` | string | ❌ | 结束日期 | — | YYYY-MM-DD，≥start_date |
| `page` | int | ❌ | 页码 | 1 | ≥1 |
| `per_page` | int | ❌ | 每页数量 | 10 | 1-50 |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "order_no": "Y2026053000012345",
      "status": "paid",
      "customer_status": "生产中",
      "thumbnail": "https://cdn.yian.com/products/101/thumb_200.webp",
      "product_count": 2,
      "total_amount": 4620.00,
      "created_at": "2026-05-30T12:00:00+08:00",
      "can_cancel": false,
      "can_pay": false,
      "can_after_sale": false
    }
  ],
  "meta": {
    "pagination": {
      "total": 45,
      "current_page": 1,
      "per_page": 10,
      "last_page": 5
    }
  }
}
```

---

### API-017: 订单详情

**GET** `/orders/{order_no}`

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "order_no": "Y2026053000012345",
    "parent_order_no": null,
    "status": "producing",
    "customer_status": "生产中",
    "status_history": [
      {"status": "pending_payment", "label": "待付款", "time": "2026-05-30T12:00:00+08:00"},
      {"status": "paid", "label": "已付款", "time": "2026-05-30T12:02:15+08:00"},
      {"status": "producing", "label": "生产中", "time": "2026-05-30T14:30:00+08:00"}
    ],
    "items": [ ... ],
    "address": { ... },
    "amounts": { ... },
    "invoice": { ... },
    "logistics": {
      "carrier_name": "顺丰速运",
      "tracking_no": "SF1234567890",
      "status": "in_transit",
      "latest_update": "2026-06-01T08:00:00+08:00",
      "latest_location": "北京市顺义区",
      "tracks": [
        {"time": "2026-06-01T08:00:00+08:00", "location": "北京市顺义区", "event": "快件已到达北京顺义集散中心"},
        {"time": "2026-05-31T20:00:00+08:00", "location": "上海市", "event": "快件已发货"}
      ]
    },
    "production": {
      "status": "printing",
      "progress": 45,
      "estimated_completion": "2026-06-02T18:00:00+08:00",
      "mes_synced_at": "2026-05-30T14:30:00+08:00"
    },
    "actions": {
      "can_cancel": false,
      "can_pay": false,
      "can_confirm_receipt": false,
      "can_apply_after_sale": false,
      "can_reprint": false
    }
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 404 | 4001 | 订单不存在 | order_no无效 |
| 403 | 4006 | 无权访问该订单 | 非当前用户订单 |

---

### API-018: 取消订单

**POST** `/orders/{order_no}/cancel`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `reason` | string | ✅ | 取消原因 | 1-200字符 |
| `reason_code` | int | ❌ | 取消原因编码 | 1:不想要了 / 2:信息填错 / 3:超时未付款 / 4:其他 |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "订单已取消",
  "data": {
    "order_no": "Y2026053000012345",
    "status": "cancelled",
    "refund_info": {
      "refund_no": "R2026053000012345",
      "amount": 4620.00,
      "status": "pending",
      "estimated_arrival": "2026-05-31T12:00:00+08:00"
    }
  }
}
```

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 409 | 4002 | 当前状态不允许取消 | status ∉ [pending_payment, paid且未生产] |
| 409 | 4007 | 订单已超过可取消时限 | pending_payment状态超过24h自动关闭 |

---

## 6. 支付系统 (Payments)

---

### API-019: 创建支付

**POST** `/payments`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `order_no` | string | 条件 | 订单号 | order_no和payment_id二选一 |
| `payment_id` | int | 条件 | 已有支付单ID | 用于重新支付 |
| `gateway` | string | ✅ | 支付渠道 | wechat_native / wechat_h5 / alipay_pc / alipay_wap / unionpay / wallet |
| `return_url` | string | 条件 | 支付完成回跳URL | H5/手机网站支付必填 |

#### 成功响应 (201)

```json
{
  "code": "0000",
  "message": "支付单创建成功",
  "data": {
    "payment_id": 9001,
    "order_no": "Y2026053000012345",
    "amount": 4620.00,
    "gateway": "wechat_native",
    "gateway_name": "微信支付",
    "status": "pending",
    "expire_at": "2026-05-30T12:05:00+08:00",
    "credential": {
      "type": "qrcode",
      "qrcode_url": "weixin://wxpay/bizpayurl?pr=xxxx",
      "qrcode_image": "https://api.yian.com/payments/9001/qrcode"
    }
  }
}
```

**不同gateway的credential格式**:

| Gateway | Type | 字段 |
|---------|------|------|
| wechat_native | qrcode | qrcode_url, qrcode_image |
| wechat_h5 | redirect | h5_url |
| alipay_pc | qrcode | qrcode_url, qrcode_image |
| alipay_wap | form | form_html (自动提交的HTML表单) |
| unionpay | redirect | redirect_url |
| wallet | direct | 余额直接扣减，无额外凭证 |

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 404 | 4001 | 订单不存在 | order_no无效 |
| 409 | 5001 | 支付已超时 | 订单创建超过5min |
| 409 | 5004 | 支付渠道不可用 | 该渠道维护中或未配置 |
| 422 | 5002 | 支付金额不匹配 | 支付金额与订单金额不一致（防篡改） |

#### 幂等性
- `Idempotency-Key` 必填，同一Key返回首次创建的支付单

---

### API-020: 查询支付状态

**GET** `/payments/{id}`

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "payment_id": 9001,
    "order_no": "Y2026053000012345",
    "amount": 4620.00,
    "gateway": "wechat_native",
    "status": "success",
    "paid_at": "2026-05-30T12:02:15+08:00",
    "transaction_no": "4200002026XXXX",
    "receipt_available": true
  }
}
```

---

### API-021: 余额充值

**POST** `/wallet/recharge`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `amount` | decimal | ✅ | 充值金额 | ≥100，≤50000，整数或2位小数 |
| `gateway` | string | ✅ | 支付渠道 | wechat_native / alipay_pc / unionpay |

#### 成功响应 (201)
- 结构与创建支付类似，但order_no为null，recharge_no为充值单号

---

## 7. 地址管理 (Addresses)

---

### API-022: 地址列表

**GET** `/addresses`

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "id": 2001,
      "contact_name": "张三",
      "phone": "13800138000",
      "province": "北京市",
      "city": "北京市",
      "district": "朝阳区",
      "detail": "xxx路xxx号xxx室",
      "full_address": "北京市朝阳区xxx路xxx号xxx室",
      "zip_code": "100000",
      "is_default": true,
      "inside_fence": true,
      "tag": "公司",
      "created_at": "2026-01-15T10:00:00+08:00"
    }
  ]
}
```

---

### API-023: 添加地址

**POST** `/addresses`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `contact_name` | string | ✅ | 联系人姓名 | 2-20字符 |
| `phone` | string | ✅ | 手机号 | 大陆手机号 |
| `province` | string | ✅ | 省 | 2-30字符 |
| `city` | string | ✅ | 市 | 2-30字符 |
| `district` | string | ✅ | 区/县 | 2-30字符 |
| `detail` | string | ✅ | 详细地址 | 5-200字符 |
| `zip_code` | string | ❌ | 邮编 | 6位数字 |
| `is_default` | bool | ❌ | 是否默认 | 默认false |
| `tag` | string | ❌ | 标签 | 公司/家/工厂/其他，默认其他 |

#### 成功响应 (201)

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 409 | 6001 | 地址超出配送范围 | inside_fence=false，提示"该地址暂不支持配送" |
| 400 | 0001 | 地址格式错误 | 省市区不匹配（如省份非标准名称） |

---

### API-024: 编辑地址

**PUT** `/addresses/{id}`

- 请求参数同添加地址

---

### API-025: 删除地址

**DELETE** `/addresses/{id}`

#### 失败响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 409 | 0001 | 存在进行中的订单使用该地址 | 该地址有关联的未完成的订单 |

---

## 8. 通知系统 (Notifications)

---

### API-026: 通知列表

**GET** `/notifications`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `type` | string | ❌ | 类型筛选 | all | order/payment/logistics/after_sale/system/promotion |
| `is_read` | bool | ❌ | 是否已读筛选 | — | true/false |
| `page` | int | ❌ | 页码 | 1 |
| `per_page` | int | ❌ | 每页数量 | 20 |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "id": 70001,
      "type": "order",
      "title": "订单已发货",
      "content": "您的订单 Y2026053000012345 已发货，顺丰速运 SF1234567890",
      "is_read": false,
      "action_url": "/orders/Y2026053000012345",
      "action_text": "查看订单",
      "icon": "truck",
      "created_at": "2026-06-01T08:00:00+08:00"
    }
  ],
  "meta": {
    "unread_count": 5,
    "pagination": {
      "total": 128,
      "current_page": 1,
      "per_page": 20,
      "last_page": 7
    }
  }
}
```

---

### API-027: 标记通知已读

**PUT** `/notifications/{id}/read`

#### 成功响应 (200)

---

### API-028: 批量标记已读

**PUT** `/notifications/read-all`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `type` | string | ❌ | 仅标记某类型为已读 |

---

## 附录A: Webhook规范

### 微信支付回调

**POST** `/webhooks/wechat-pay`

#### 请求头

| 字段 | 说明 |
|------|------|
| `Wechatpay-Signature` | 签名 |
| `Wechatpay-Timestamp` | 时间戳 |
| `Wechatpay-Nonce` | 随机串 |
| `Wechatpay-Serial` | 平台证书序列号 |

#### 请求体 (解密后)

```json
{
  "id": "ev-1234567890",
  "create_time": "2026-05-30T12:02:15+08:00",
  "resource_type": "encrypt-resource",
  "resource": {
    "algorithm": "AEAD_AES_256_GCM",
    "ciphertext": "...",
    "associated_data": "transaction",
    "nonce": "...",
    "original_type": "transaction"
  },
  "summary": "支付成功"
}
```

#### 解密后原始数据

```json
{
  "transaction_id": "4200002026XXXX",
  "out_trade_no": "Y2026053000012345",
  "trade_state": "SUCCESS",
  "trade_state_desc": "支付成功",
  "bank_type": "CMB",
  "attach": "",
  "success_time": "2026-05-30T12:02:15+08:00",
  "payer": {
    "openid": "oUpF8uMuAJO_M2pxb1Q9zNjWeS6o"
  },
  "amount": {
    "total": 462000,
    "payer_total": 462000,
    "currency": "CNY"
  }
}
```

#### 响应要求
- HTTP 200 + 响应体 `{"code": "SUCCESS"}`
- 处理失败也返回200（避免微信重试机制异常），但记录错误日志

---

### 支付宝回调

**POST** `/webhooks/alipay`

#### 请求体 (Form Data)

| 字段 | 说明 |
|------|------|
| `out_trade_no` | 商户订单号 |
| `trade_no` | 支付宝交易号 |
| `trade_status` | TRADE_SUCCESS / TRADE_CLOSED |
| `total_amount` | 订单金额 |
| `sign` | RSA2签名 |
| `sign_type` | RSA2 |

#### 响应要求
- 成功：`success`（纯文本，不含引号）
- 失败：任意非success字符串，支付宝将重试

---

## 附录B: 文件上传规范

### 直传签名获取

**POST** `/upload/sign`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 | 校验规则 |
|------|------|:----:|------|---------|
| `file_name` | string | ✅ | 原始文件名 | 含扩展名，长度≤200 |
| `file_size` | int | ✅ | 文件大小(字节) | >0，≤5GB |
| `file_type` | string | ✅ | 文件MIME类型 | application/pdf / image/jpeg等 |
| `purpose` | string | ✅ | 用途 | product_image / user_file / prepress_pdf |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "host": "https://oss.yian.com",
    "key": "products/2026/05/30/abc123.pdf",
    "policy": "eyJleHBpcmF0aW9uIjoiMj...",
    "signature": "xxxx",
    "access_key_id": "LTAIxxxx",
    "callback": "eyJjYWxsYmFja0JvZHkiOi...",
    "callback_url": "https://api.yian.com/upload/callback",
    "max_size": 524288000
  }
}
```

### 分片上传初始化

**POST** `/upload/init`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `file_name` | string | ✅ | 文件名 |
| `file_size` | int | ✅ | 文件大小 |
| `total_chunks` | int | ✅ | 总分片数 |

#### 成功响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "upload_id": "upload_abc123",
    "chunk_size": 5242880,
    "expires_at": "2026-05-31T12:00:00+08:00"
  }
}
```

### 分片上传

**POST** `/upload/chunk`

- Content-Type: `multipart/form-data`
- 字段：`upload_id`, `chunk_index`, `chunk` (binary)

### 完成分片上传

**POST** `/upload/complete`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `upload_id` | string | ✅ | 上传ID |
| `chunk_md5s` | string[] | ✅ | 各分片MD5数组 |

---

*本文档与主实施计划配套使用，API变更时需同步更新两端。*


---

# 第二部分: 后台管理API (Admin APIs)

> 本部分原独立为《后台管理API规范》，现合并至此，与第一部分共用基础URL前缀 `/api/v1/admin`。

## 1. 通用规范

### 1.1 通用响应结构

同主文档API详细规范附录A。

### 1.2 权限响应

| HTTP | Code | Message | 触发条件 |
|:----:|:----:|---------|---------|
| 403 | 1009 | 无权限访问该资源 | RBAC校验失败 |
| 403 | 1010 | 数据隔离限制 | factory_manager查看非分配订单 |

### 1.3 数据隔离规则

| 角色 | 数据范围 |
|------|---------|
| super_admin | 全部数据 |
| operator | 全部数据（除财务敏感数据） |
| customer_service | 客户数据 + 订单数据 + 售后数据 |
| factory_manager | 分配给本工厂的订单 + 生产数据 |
| finance | 财务数据 + 退款/发票 + 对账 |

---

## 2. Dashboard

### AD-001: Dashboard统计数据

**GET** `/dashboard/stats`

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "today": {
      "order_count": 128,
      "order_count_change": 12,
      "gmv": 45280.00,
      "gmv_change": 8,
      "new_users": 15,
      "pending_after_sales": 5,
      "producing_orders": 32
    },
    "charts": {
      "order_trend": {
        "dates": ["05-24", "05-25", "05-26", "05-27", "05-28", "05-29", "05-30"],
        "order_counts": [98, 105, 112, 108, 115, 120, 128],
        "gmv_values": [35000, 38000, 42000, 40000, 43000, 44500, 45280]
      },
      "product_ranking": [
        {"name": "A4企业宣传册", "sales": 520},
        {"name": "铜版纸名片", "sales": 380},
        {"name": "A2海报", "sales": 256}
      ],
      "status_distribution": [
        {"status": "待付款", "count": 15, "percentage": 12},
        {"status": "生产中", "count": 32, "percentage": 25},
        {"status": "待收货", "count": 28, "percentage": 22},
        {"status": "已完成", "count": 53, "percentage": 41}
      ]
    },
    "alerts": [
      {"type": "warning", "message": "3个订单即将超时未发货", "link": "/admin/orders?status=paid&deadline=tomorrow"},
      {"type": "danger", "message": "1个售后申请待审核超24h", "link": "/admin/after-sales?status=pending"}
    ]
  }
}
```

---

## 3. 客户管理 (Customers)

### AD-010: 客户列表

**GET** `/customers`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `keyword` | string | ❌ | 手机号/公司名关键词 | — |
| `user_type` | int | ❌ | 用户类型 | all | 1:个人 / 2:企业 |
| `company_auth_status` | int | ❌ | 认证状态 | all | 0-4 |
| `vip_level` | int | ❌ | VIP等级 | all | 0-5 |
| `status` | int | ❌ | 账号状态 | all | 0:禁用 / 1:正常 / 2:锁定 |
| `date_from` | string | ❌ | 注册起始日期 | — |
| `date_to` | string | ❌ | 注册结束日期 | — |
| `page` | int | ❌ | 页码 | 1 |
| `per_page` | int | ❌ | 每页数量 | 20 |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "id": 10001,
      "phone": "138****8000",
      "user_type": 2,
      "company_name": "北京怡安印刷科技有限公司",
      "company_auth_status": 2,
      "company_auth_status_label": "已认证",
      "vip_level": 3,
      "vip_name": "钻石会员",
      "order_count": 45,
      "total_gmv": 128000.00,
      "balance": 5000.00,
      "points": 2580,
      "status": 1,
      "last_login_at": "2026-05-30T10:00:00+08:00",
      "created_at": "2026-01-15T10:00:00+08:00"
    }
  ],
  "meta": {
    "pagination": { "total": 156, "current_page": 1, "per_page": 20, "last_page": 8 }
  }
}
```

---

### AD-011: 客户详情

**GET** `/customers/{id}`

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "id": 10001,
    "phone": "138****8000",
    "user_type": 2,
    "company": {
      "name": "北京怡安印刷科技有限公司",
      "short_name": "怡安印刷",
      "credit_code": "91110108MA00XXXX0X",
      "legal_person": "张三",
      "auth_status": 2,
      "auth_materials": ["https://cdn.yian.com/certs/xxx.jpg"],
      "auth_at": "2026-02-01T10:00:00+08:00"
    },
    "vip": {
      "level": 3,
      "name": "钻石会员",
      "discount": 0.88,
      "points": 2580,
      "next_level_points": 50000,
      "progress": 51
    },
    "statistics": {
      "order_count": 45,
      "total_gmv": 128000.00,
      "avg_order_amount": 2844.44,
      "last_order_at": "2026-05-28T14:00:00+08:00",
      "refund_count": 2,
      "refund_rate": 4.4
    },
    "recent_orders": [
      {"order_no": "Y202605280001", "total_amount": 3200.00, "status": "completed", "created_at": "2026-05-28T14:00:00+08:00"}
    ],
    "addresses": [...],
    "login_devices": [...]
  }
}
```

---

### AD-012: 更新客户状态

**PUT** `/customers/{id}/status`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `status` | int | ✅ | 0:禁用 / 1:正常 / 2:锁定 |
| `reason` | string | 条件 | status=0/2时必填 |

---

### AD-013: 企业认证审核

**PUT** `/customers/{id}/company-auth`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `auth_status` | int | ✅ | 2:通过 / 3:拒绝 / 4:需重新提交 |
| `rejected_reason` | string | 条件 | auth_status=3/4时必填 |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "审核完成",
  "data": {
    "customer_id": 10001,
    "company_auth_status": 2,
    "auth_at": "2026-05-30T12:00:00+08:00"
  }
}
```

---

### AD-014: 客户订单历史

**GET** `/customers/{id}/orders`

- 请求参数同订单列表
- 自动筛选customer_id

---

## 4. 订单管理 (Orders)

### AD-020: 订单列表

**GET** `/orders`

#### 补充筛选参数

| 字段 | 类型 | 说明 |
|------|------|------|
| `payment_status` | string | pending/success/failed/refunded |
| `has_after_sale` | bool | 是否有售后 |
| `is_urgent` | bool | 是否加急 |
| `deadline_from` | string | 交付日期起始 |
| `deadline_to` | string | 交付日期结束 |

#### 补充响应字段

```json
{
  "delivery_deadline": "2026-06-05",
  "days_to_deadline": 6,
  "is_overdue": false,
  "production_progress": 45,
  "can_dispatch": true,
  "can_assign_factory": true
}
```

---

### AD-021: 订单详情（Admin视角）

**GET** `/orders/{order_no}`

#### 补充Admin字段

```json
{
  "internal_notes": [
    {"content": "客户要求加急", "created_by": "客服小李", "created_at": "2026-05-30T10:00:00"}
  ],
  "audit_trail": [
    {"action": "状态变更: 待付款→已付款", "operator": "系统", "time": "2026-05-30T12:02:15"},
    {"action": "分配工厂: 上海印刷厂A", "operator": "运营小王", "time": "2026-05-30T14:00:00"}
  ],
  "risk_flags": [
    {"type": "amount", "level": "low", "message": "单笔金额>¥5,000"}
  ],
  "profit_analysis": {
    "revenue": 4620.00,
    "estimated_cost": 2800.00,
    "estimated_profit": 1820.00,
    "profit_margin": 39.4
  }
}
```

---

### AD-022: 分配工厂

**PUT** `/orders/{order_no}/assign-factory`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `factory_id` | int | ✅ | 工厂ID |
| `sub_order_configs` | array | 条件 | 拆分时各子订单的工厂分配 |

---

### AD-023: 订单批量操作

**POST** `/orders/batch`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `order_nos` | string[] | ✅ | 订单号数组 |
| `action` | string | ✅ | export/assign_factory/update_status/print |
| `params` | object | 条件 | action对应参数 |

---

### AD-024: 订单导出

**POST** `/orders/export`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `filters` | object | ✅ | 同列表筛选条件 |
| `columns` | string[] | ✅ | 导出字段列表 |
| `format` | string | ❌ | xlsx/csv，默认xlsx |

#### 响应 (202)

```json
{
  "code": "0000",
  "message": "导出任务已创建",
  "data": {
    "task_id": "export_abc123",
    "status": "pending",
    "estimated_seconds": 30
  }
}
```

---

### AD-025: 获取导出任务状态

**GET** `/export-tasks/{task_id}`

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "task_id": "export_abc123",
    "status": "completed",
    "file_url": "https://cdn.yian.com/exports/orders_20260530.xlsx",
    "file_size": 128000,
    "expired_at": "2026-05-31T12:00:00+08:00"
  }
}
```

---

## 5. 商品管理 (Products)

### AD-030: 商品上下架

**PUT** `/products/{id}/status`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `status` | string | ✅ | on_sale/off_shelf/draft/pending_review |
| `reason` | string | 条件 | 下架时必填 |

---

### AD-031: 商品批量操作

**POST** `/products/batch`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `product_ids` | int[] | ✅ | 商品ID数组 |
| `action` | string | ✅ | on_sale/off_shelf/delete/update_category/update_sort |
| `params` | object | 条件 | action对应参数 |

---

### AD-032: 分类管理CRUD

**GET** `/categories` — 分类树  
**POST** `/categories` — 创建分类  
**PUT** `/categories/{id}` — 编辑分类  
**DELETE** `/categories/{id}` — 删除分类（有商品时禁止）

#### 分类表单字段

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `name` | string | ✅ | 分类名 |
| `parent_id` | int | ❌ | 父分类ID，0为顶级 |
| `icon` | string | ❌ | 图标URL |
| `image` | string | ❌ | 封面图 |
| `sort` | int | ❌ | 排序 |
| `seo_title` | string | ❌ | SEO标题 |

---

## 6. 财务管理 (Finance)

### AD-040: 对账中心

**GET** `/finance/reconciliation`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `date` | string | ✅ | 对账日期 | — | YYYY-MM-DD |
| `gateway` | string | ❌ | 支付渠道 | all | wechat/alipay/unionpay |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "date": "2026-05-30",
    "summary": {
      "total_orders": 128,
      "total_amount": 45280.00,
      "wechat_amount": 25000.00,
      "alipay_amount": 18000.00,
      "wallet_amount": 2280.00
    },
    "gateway_statements": {
      "wechat": {
        "system_count": 85,
        "system_amount": 25000.00,
        "gateway_count": 85,
        "gateway_amount": 25000.00,
        "diff_count": 0,
        "diff_amount": 0.00,
        "status": "matched"
      },
      "alipay": {
        "system_count": 40,
        "system_amount": 18000.00,
        "gateway_count": 40,
        "gateway_amount": 18000.00,
        "diff_count": 0,
        "diff_amount": 0.00,
        "status": "matched"
      }
    },
    "differences": [],
    "unreconciled_payments": []
  }
}
```

---

### AD-041: 退款审核列表

**GET** `/finance/refunds`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `status` | string | ❌ | 状态 | pending | pending/approved/rejected/processing/completed |
| `date_from` | string | ❌ | 起始日期 | — |
| `date_to` | string | ❌ | 结束日期 | — |
| `page` | int | ❌ | 页码 | 1 |
| `per_page` | int | ❌ | 每页数量 | 20 |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "refund_no": "R2026053000012345",
      "order_no": "Y2026053000012345",
      "customer": {
        "id": 10001,
        "company_name": "北京怡安印刷科技有限公司"
      },
      "amount": 4620.00,
      "reason": "印刷色差严重",
      "reason_code": 3,
      "type": 1,
      "status": 1,
      "status_label": "待审核",
      "images": ["https://cdn.yian.com/after_sales/xxx.jpg"],
      "applied_at": "2026-05-30T10:00:00+08:00",
      "can_approve": true,
      "can_reject": true
    }
  ]
}
```

---

### AD-042: 退款审核

**PUT** `/finance/refunds/{refund_no}/audit`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `action` | string | ✅ | approve/reject |
| `approved_amount` | decimal | 条件 | approve时必填，≤申请金额 |
| `remark` | string | 条件 | approve/reject时必填 |

---

### AD-043: 发票审核列表

**GET** `/finance/invoices`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `status` | string | ❌ | 状态 | all | pending/issued/failed/void/red |
| `type` | int | ❌ | 发票类型 | all | 1:普票 / 2:专票 |
| `date_from` | string | ❌ | — | — |
| `date_to` | string | ❌ | — | — |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "invoice_no": "01100190001112345678",
      "order_no": "Y2026053000012345",
      "type": 1,
      "type_label": "电子普票",
      "status": 3,
      "status_label": "已开具",
      "title": "北京怡安印刷科技有限公司",
      "tax_no": "91110108MA00XXXX0X",
      "amount": 4620.00,
      "tax_amount": 262.64,
      "total_amount": 4620.00,
      "email": "finance@yian.com",
      "applied_at": "2026-05-30T10:00:00+08:00",
      "issued_at": "2026-05-30T10:05:00+08:00",
      "pdf_url": "https://cdn.yian.com/invoices/xxx.pdf"
    }
  ]
}
```

---

### AD-044: 财务报表

**GET** `/finance/reports`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `report_type` | string | ✅ | 报表类型 | — | daily/weekly/monthly |
| `date_from` | string | ✅ | — | — |
| `date_to` | string | ✅ | — | — |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "summary": {
      "total_revenue": 1280000.00,
      "total_refund": 45000.00,
      "net_revenue": 1235000.00,
      "total_orders": 568,
      "avg_order_value": 2253.52
    },
    "daily_breakdown": [
      {
        "date": "2026-05-30",
        "revenue": 45280.00,
        "refund": 0.00,
        "orders": 128,
        "new_customers": 15
      }
    ],
    "payment_method_breakdown": [
      {"gateway": "wechat", "amount": 680000.00, "percentage": 53},
      {"gateway": "alipay", "amount": 450000.00, "percentage": 35},
      {"gateway": "wallet", "amount": 150000.00, "percentage": 12}
    ]
  }
}
```

---

## 7. 工厂管理 (Factories)

### AD-050: 工厂列表

**GET** `/factories`

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "id": 1,
      "name": "上海印刷厂A",
      "code": "SH-A",
      "contact_name": "王厂长",
      "contact_phone": "139****1234",
      "address": "上海市浦东新区xxx路xxx号",
      "status": 1,
      "capacity": {
        "daily_max": 50000,
        "current_load": 32000,
        "utilization": 64
      },
      "equipment": [
        {"name": "海德堡CD102对开四色", "status": "running"},
        {"name": "小森Lithrone G40", "status": "running"}
      ],
      "rating": 4.8,
      "on_time_rate": 96.5,
      "quality_pass_rate": 98.2,
      "is_preferred": true
    }
  ]
}
```

---

### AD-051: 排产甘特图数据

**GET** `/factories/{id}/schedule`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `date_from` | string | ✅ | — | — | YYYY-MM-DD |
| `date_to` | string | ✅ | — | — | YYYY-MM-DD |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "factory_id": 1,
    "factory_name": "上海印刷厂A",
    "date_range": ["2026-05-30", "2026-06-05"],
    "machines": [
      {
        "machine_id": 1,
        "machine_name": "海德堡CD102",
        "jobs": [
          {
            "order_no": "Y2026053000012345",
            "product_name": "A4企业宣传册",
            "quantity": 1000,
            "start_time": "2026-05-30T08:00:00",
            "end_time": "2026-05-30T16:00:00",
            "status": "running",
            "priority": "normal",
            "progress": 45
          }
        ]
      }
    ]
  }
}
```

---

### AD-052: 更新生产进度

**PUT** `/factories/{id}/jobs/{order_no}/progress`

#### 请求参数 (Body)

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `progress` | int | ✅ | 进度百分比 0-100 |
| `status` | string | ❌ | 当前工序状态 |
| `estimated_completion` | string | ❌ | 预计完成时间 |
| `remark` | string | ❌ | 备注 |

---

## 8. 内容管理 (Content)

### AD-060: Banner管理CRUD

**GET** `/banners` — 列表  
**POST** `/banners` — 创建  
**PUT** `/banners/{id}` — 编辑  
**DELETE** `/banners/{id}` — 删除  
**PUT** `/banners/{id}/sort` — 调整排序

#### Banner表单字段

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `title` | string | ❌ | 标题 |
| `image` | string | ✅ | PC端图片 |
| `image_mobile` | string | ❌ | 移动端图片 |
| `link_type` | string | ✅ | product/category/url |
| `link_target` | string | ✅ | 跳转目标 |
| `sort` | int | ❌ | 排序 |
| `position` | string | ✅ | home/category等 |
| `display_start` | string | ❌ | 展示开始时间 |
| `display_end` | string | ❌ | 展示结束时间 |
| `status` | int | ✅ | 0/1 |

---

### AD-061: 文章/帮助管理CRUD

**GET** `/articles` — 列表  
**POST** `/articles` — 创建  
**PUT** `/articles/{id}` — 编辑  
**DELETE** `/articles/{id}` — 删除  
**PUT** `/articles/{id}/publish` — 发布  
**PUT** `/articles/{id}/offline` — 下线

#### 文章表单字段

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `title` | string | ✅ | 标题 |
| `slug` | string | ❌ | URL别名 |
| `category_id` | int | ❌ | 分类 |
| `type` | int | ✅ | 1:新闻 / 2:公告 / 3:帮助 / 4:SEO |
| `content` | string | ✅ | 富文本 |
| `summary` | string | ❌ | 摘要 |
| `cover` | string | ❌ | 封面 |
| `status` | int | ✅ | 0:草稿 / 1:已发布 / 2:已下线 |
| `published_at` | string | ❌ | 发布时间 |
| `sort` | int | ❌ | 排序 |

---

## 9. 系统管理 (System)

### AD-070: 管理员账号管理

**GET** `/admins` — 列表  
**POST** `/admins` — 创建  
**PUT** `/admins/{id}` — 编辑  
**PUT** `/admins/{id}/status` — 启用/禁用  
**PUT** `/admins/{id}/reset-password` — 重置密码  
**DELETE** `/admins/{id}` — 删除（不能删除自己）

#### 管理员表单字段

| 字段 | 类型 | 必填 | 说明 |
|------|------|:----:|------|
| `username` | string | ✅ | 3-20字符，UQ |
| `real_name` | string | ✅ | 真实姓名 |
| `email` | string | ❌ | 邮箱 |
| `phone` | string | ❌ | 手机号 |
| `role_id` | int | ✅ | 角色ID |
| `password` | string | 条件 | 创建时必填，8-32位 |
| `status` | int | ✅ | 0/1 |

---

### AD-071: 角色权限管理

**GET** `/roles` — 角色列表  
**POST** `/roles` — 创建角色  
**PUT** `/roles/{id}` — 编辑角色  
**DELETE** `/roles/{id}` — 删除角色（有管理员时禁止）  
**GET** `/permissions` — 权限列表（树形）  
**PUT** `/roles/{id}/permissions` — 分配权限

#### 角色权限分配请求

```json
{
  "permission_ids": [1, 2, 3, 5, 8],
  "data_scope": 1
}
```

---

### AD-072: 系统配置管理

**GET** `/system-configs` — 配置列表  
**PUT** `/system-configs/{key}` — 更新配置  
**POST** `/system-configs/batch` — 批量更新

#### 配置项示例

| Key | 类型 | 说明 |
|-----|------|------|
| `site.logo` | string | 站点Logo |
| `site.icp` | string | ICP备案号 |
| `order.auto_cancel_minutes` | int | 订单自动取消时间(分钟) |
| `order.max_modifications` | int | 最大修改次数 |
| `freight.free_threshold` | decimal | 包邮金额阈值 |
| `payment.wechat_enabled` | bool | 微信支付开关 |
| `notification.sms_enabled` | bool | 短信通知开关 |

---

### AD-073: 审计日志

**GET** `/audit-logs`

#### 请求参数 (Query)

| 字段 | 类型 | 必填 | 说明 | 默认值 |
|------|------|:----:|------|:------:|
| `admin_id` | int | ❌ | 操作人 | — |
| `module` | string | ❌ | 模块 | all |
| `action` | string | ❌ | 操作类型 | all | create/update/delete/login/export |
| `date_from` | string | ❌ | — | — |
| `date_to` | string | ❌ | — | — |
| `page` | int | ❌ | 页码 | 1 |
| `per_page` | int | ❌ | 每页数量 | 20 |

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": [
    {
      "id": 1,
      "admin_name": "运营小王",
      "action": "update",
      "module": "order",
      "resource_type": "Order",
      "resource_id": "Y2026053000012345",
      "old_values": {"fm_status": 20},
      "new_values": {"fm_status": 40},
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-05-30T14:00:00+08:00"
    }
  ]
}
```

---

### AD-074: 操作日志详情

**GET** `/audit-logs/{id}`

#### 响应 (200)

```json
{
  "code": "0000",
  "message": "success",
  "data": {
    "id": 1,
    "admin": {"id": 2, "username": "operator", "real_name": "运营小王"},
    "action": "update",
    "module": "order",
    "resource_type": "Order",
    "resource_id": "Y2026053000012345",
    "old_values": {
      "fm_status": 20,
      "factory_id": null
    },
    "new_values": {
      "fm_status": 40,
      "factory_id": 1
    },
    "diff": [
      {"field": "fm_status", "old": "已付款", "new": "印刷中"},
      {"field": "factory_id", "old": "未分配", "new": "上海印刷厂A"}
    ],
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "created_at": "2026-05-30T14:00:00+08:00"
  }
}
```

---

## 附录: Admin API汇总表

| 接口 | 方法 | 路径 | 权限 |
|------|------|------|------|
| Dashboard统计 | GET | `/admin/dashboard/stats` | all |
| 客户列表 | GET | `/admin/customers` | admin, customer_service |
| 客户详情 | GET | `/admin/customers/{id}` | admin, customer_service |
| 更新客户状态 | PUT | `/admin/customers/{id}/status` | admin, customer_service |
| 企业认证审核 | PUT | `/admin/customers/{id}/company-auth` | admin, operator |
| 订单列表 | GET | `/admin/orders` | admin, operator, factory_manager, customer_service |
| 订单详情 | GET | `/admin/orders/{order_no}` | admin, operator, factory_manager, customer_service |
| 更新订单状态 | PUT | `/admin/orders/{order_no}/status` | admin, operator, factory_manager |
| 分配工厂 | PUT | `/admin/orders/{order_no}/assign-factory` | admin, operator |
| 订单批量操作 | POST | `/admin/orders/batch` | admin, operator |
| 订单导出 | POST | `/admin/orders/export` | admin, operator, finance |
| 导出任务状态 | GET | `/admin/export-tasks/{task_id}` | all |
| 商品列表 | GET | `/admin/products` | admin, operator |
| 创建商品 | POST | `/admin/products` | admin, operator |
| 编辑商品 | PUT | `/admin/products/{id}` | admin, operator |
| 商品上下架 | PUT | `/admin/products/{id}/status` | admin, operator |
| 商品批量操作 | POST | `/admin/products/batch` | admin, operator |
| 分类列表 | GET | `/admin/categories` | admin, operator |
| 创建分类 | POST | `/admin/categories` | admin, operator |
| 编辑分类 | PUT | `/admin/categories/{id}` | admin, operator |
| 删除分类 | DELETE | `/admin/categories/{id}` | admin, operator |
| 对账中心 | GET | `/admin/finance/reconciliation` | admin, finance |
| 退款审核列表 | GET | `/admin/finance/refunds` | admin, finance, customer_service |
| 退款审核 | PUT | `/admin/finance/refunds/{refund_no}/audit` | admin, finance |
| 发票列表 | GET | `/admin/finance/invoices` | admin, finance |
| 发票重开/红冲 | POST | `/admin/finance/invoices/{id}/red` | admin, finance |
| 财务报表 | GET | `/admin/finance/reports` | admin, finance |
| 工厂列表 | GET | `/admin/factories` | admin, factory_manager |
| 排产甘特图 | GET | `/admin/factories/{id}/schedule` | admin, factory_manager |
| 更新生产进度 | PUT | `/admin/factories/{id}/jobs/{order_no}/progress` | admin, factory_manager |
| Banner列表 | GET | `/admin/banners` | admin, operator |
| Banner创建 | POST | `/admin/banners` | admin, operator |
| Banner编辑 | PUT | `/admin/banners/{id}` | admin, operator |
| Banner删除 | DELETE | `/admin/banners/{id}` | admin, operator |
| 文章列表 | GET | `/admin/articles` | admin, operator |
| 文章创建 | POST | `/admin/articles` | admin, operator |
| 文章编辑 | PUT | `/admin/articles/{id}` | admin, operator |
| 文章删除 | DELETE | `/admin/articles/{id}` | admin, operator |
| 管理员列表 | GET | `/admin/admins` | admin |
| 创建管理员 | POST | `/admin/admins` | admin |
| 编辑管理员 | PUT | `/admin/admins/{id}` | admin |
| 重置密码 | PUT | `/admin/admins/{id}/reset-password` | admin |
| 角色列表 | GET | `/admin/roles` | admin |
| 创建角色 | POST | `/admin/roles` | admin |
| 权限列表 | GET | `/admin/permissions` | admin |
| 分配权限 | PUT | `/admin/roles/{id}/permissions` | admin |
| 系统配置 | GET | `/admin/system-configs` | admin |
| 更新配置 | PUT | `/admin/system-configs/{key}` | admin |
| 审计日志 | GET | `/admin/audit-logs` | admin |
| 审计日志详情 | GET | `/admin/audit-logs/{id}` | admin |

**总计: 52个Admin API端点**

---

*本文档与主文档API详细规范配套使用，Admin API变更需同步更新前端管理后台页面字段规格。*
