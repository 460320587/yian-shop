# 怡安印刷商城 — 关键表Migration可执行代码

> **版本**: v1.0  
> **日期**: 2026-05-30  
> **用途**: 开发可直接复制到 `database/migrations/` 的Migration文件  
> **技术栈**: Laravel 13.x Migration + PHP 8.5

---

## 目录

1. [customers 用户表](#1-customers-用户表)
2. [customer_addresses 客户地址表](#2-customer_addresses-客户地址表)
3. [products 商品表](#3-products-商品表)
4. [product_categories 商品分类表](#4-product_categories-商品分类表)
5. [orders 订单主表](#5-orders-订单主表)
6. [order_items 订单商品表](#6-order_items-订单商品表)
7. [payments 支付记录表](#7-payments-支付记录表)
8. [refund_records 退款记录表](#8-refund_records-退款记录表)
9. [coupons 优惠券表](#9-coupons-优惠券表)
10. [customer_coupons 用户优惠券表](#10-customer_coupons-用户优惠券表)
11. [invoices 发票表](#11-invoices-发票表)
12. [after_sales 售后表](#12-after_sales-售后表)
13. [factories 印刷工厂表](#13-factories-印刷工厂表)
14. [shipping_templates 运费模板表](#14-shipping_templates-运费模板表)
15. [notifications 通知表](#15-notifications-通知表)
16. [admins 管理员表](#16-admins-管理员表)
17. [audit_logs 审计日志表](#17-audit_logs-审计日志表)
18. [customer_points 积分明细表](#18-customer_points-积分明细表)
19. [product_inventories 库存表](#19-product_inventories-库存表)

---

## 1. customers 用户表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id()->comment('客户ID');
            $table->string('phone', 20)->unique()->comment('手机号');
            $table->string('password', 255)->comment('密码bcrypt');
            $table->string('nickname', 50)->nullable()->comment('昵称');
            $table->string('avatar', 500)->nullable()->comment('头像URL');
            $table->unsignedTinyInteger('customer_type')->default(3)->comment('客户类型 1个人2企业3个体4工厂');
            $table->unsignedTinyInteger('vip_level')->default(0)->comment('VIP等级 0-8');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0禁用1正常');
            $table->string('email', 100)->nullable()->unique()->comment('邮箱');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->unsignedSmallInteger('risk_score')->default(0)->comment('风控评分0-100');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();

            $table->index('customer_type', 'idx_customer_type');
            $table->index('vip_level', 'idx_vip_level');
            $table->index('status', 'idx_status');
            $table->index('risk_score', 'idx_risk_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
```

## 2. customer_addresses 客户地址表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id()->comment('地址ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->string('contact_name', 50)->comment('联系人');
            $table->string('phone', 20)->comment('联系电话');
            $table->string('province', 50)->comment('省');
            $table->string('city', 50)->comment('市');
            $table->string('district', 50)->comment('区');
            $table->string('detail_address', 255)->comment('详细地址');
            $table->string('zip_code', 10)->nullable()->comment('邮编');
            $table->boolean('is_default')->default(false)->comment('是否默认');
            $table->string('label', 20)->nullable()->comment('标签 家/公司/工厂');
            $table->timestamps();

            $table->index('customer_id', 'idx_customer_id');
            $table->index(['customer_id', 'is_default'], 'idx_customer_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
```

## 3. products 商品表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id()->comment('商品ID');
            $table->foreignId('category_id')
                ->constrained('product_categories')
                ->onDelete('restrict')
                ->comment('分类ID');
            $table->string('name', 100)->comment('商品名称');
            $table->string('subtitle', 200)->nullable()->comment('副标题');
            $table->text('description')->nullable()->comment('详情');
            $table->json('images')->nullable()->comment('图片列表');
            $table->unsignedTinyInteger('status')->default(1)
                ->comment('状态 0草稿1上架2下架3审核中');
            $table->unsignedSmallInteger('sort')->default(0)->comment('排序');
            $table->boolean('is_recommended')->default(false)->comment('是否推荐');
            $table->json('pricing_params')->comment('计价参数JSON');
            $table->json('specifications')->nullable()->comment('规格参数JSON');
            $table->string('design_template', 500)->nullable()->comment('设计模板URL');
            $table->unsignedInteger('view_count')->default(0)->comment('浏览量');
            $table->unsignedInteger('sold_count')->default(0)->comment('销量');
            $table->timestamps();

            $table->index('category_id', 'idx_category_id');
            $table->index('status', 'idx_status');
            $table->index('sort', 'idx_sort');
            $table->index('is_recommended', 'idx_is_recommended');
            $table->fullText('name', 'ft_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

## 4. product_categories 商品分类表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id()->comment('分类ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父分类ID 0=一级');
            $table->string('name', 50)->comment('分类名称');
            $table->string('icon', 200)->nullable()->comment('图标');
            $table->unsignedSmallInteger('sort')->default(0)->comment('排序');
            $table->boolean('is_show')->default(true)->comment('是否显示');
            $table->unsignedTinyInteger('level')->default(1)->comment('层级 1/2/3');
            $table->string('path', 100)->default('')->comment('层级路径 1,2,3');
            $table->timestamps();

            $table->index('parent_id', 'idx_parent_id');
            $table->index('is_show', 'idx_is_show');
            $table->index('level', 'idx_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
```

## 5. orders 订单主表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id()->comment('订单ID');
            $table->string('order_no', 20)->unique()->comment('订单号 Y+年月日+6位流水');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->foreignId('address_id')
                ->constrained('customer_addresses')
                ->onDelete('restrict')
                ->comment('地址ID');
            $table->foreignId('factory_id')
                ->nullable()
                ->constrained('factories')
                ->onDelete('set null')
                ->comment('分配工厂ID');
            $table->unsignedTinyInteger('status')->default(10)->comment('FM状态 见状态机');
            $table->unsignedTinyInteger('customer_status')->default(1)->comment('nM客户状态 1待确认...');
            $table->unsignedTinyInteger('order_type')->default(1)->comment('订单类型 1普通2加急3VIP...');
            $table->unsignedBigInteger('total_amount')->comment('订单总金额 分');
            $table->unsignedBigInteger('paid_amount')->default(0)->comment('已支付金额 分');
            $table->unsignedBigInteger('discount_amount')->default(0)->comment('优惠金额 分');
            $table->string('coupon_code', 20)->nullable()->comment('使用优惠券码');
            $table->boolean('use_points')->default(false)->comment('是否使用积分');
            $table->unsignedInteger('points_used')->default(0)->comment('使用积分数');
            $table->boolean('use_balance')->default(false)->comment('是否使用余额');
            $table->unsignedBigInteger('balance_used')->default(0)->comment('使用余额 分');
            $table->json('address_snapshot')->comment('地址快照');
            $table->json('invoice_info')->nullable()->comment('发票信息快照');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->dateTime('deadline')->nullable()->comment('交期');
            $table->boolean('is_urgent')->default(false)->comment('是否加急');
            $table->unsignedTinyInteger('urgent_days')->default(0)->comment('加急天数');
            $table->string('express_company', 50)->nullable()->comment('快递公司');
            $table->string('express_no', 50)->nullable()->comment('运单号');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamp('shipped_at')->nullable()->comment('发货时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamp('cancelled_at')->nullable()->comment('取消时间');
            $table->string('cancel_reason', 200)->nullable()->comment('取消原因');
            $table->timestamps();

            $table->index('order_no', 'uk_order_no');
            $table->index('customer_id', 'idx_customer_id');
            $table->index('factory_id', 'idx_factory_id');
            $table->index('status', 'idx_status');
            $table->index('customer_status', 'idx_customer_status');
            $table->index('order_type', 'idx_order_type');
            $table->index('created_at', 'idx_created_at');
            $table->index(['customer_id', 'status'], 'idx_customer_status_composite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

## 6. order_items 订单商品表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id()->comment('ID');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict')
                ->comment('商品ID');
            $table->string('product_name', 100)->comment('商品名称快照');
            $table->string('product_image', 500)->nullable()->comment('商品图片快照');
            $table->json('specs')->comment('规格JSON 纸张/色数/工艺');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->unsignedInteger('unit_price')->comment('单价 分');
            $table->unsignedBigInteger('total_price')->comment('小计 分');
            $table->foreignId('factory_id')
                ->nullable()
                ->constrained('factories')
                ->onDelete('set null')
                ->comment('子订单分配工厂');
            $table->boolean('is_urgent')->default(false)->comment('是否加急');
            $table->json('design_files')->nullable()->comment('设计文件URLs');
            $table->timestamps();

            $table->index('order_id', 'idx_order_id');
            $table->index('product_id', 'idx_product_id');
            $table->index('factory_id', 'idx_factory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
```

## 7. payments 支付记录表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id()->comment('支付ID');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->string('payment_no', 32)->unique()->comment('支付单号');
            $table->unsignedBigInteger('amount')->comment('支付金额 分');
            $table->unsignedTinyInteger('pay_type')->comment('支付方式 1微信2支付宝...');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0待支付1成功2失败3关闭');
            $table->string('gateway_trade_no', 64)->nullable()->comment('网关流水号');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->unsignedBigInteger('refunded_amount')->default(0)->comment('已退款金额 分');
            $table->json('gateway_response')->nullable()->comment('网关回调原始数据');
            $table->timestamps();

            $table->index('order_id', 'idx_order_id');
            $table->index('customer_id', 'idx_customer_id');
            $table->index('payment_no', 'uk_payment_no');
            $table->index('status', 'idx_status');
            $table->index('gateway_trade_no', 'idx_gateway_trade_no');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

## 8. refund_records 退款记录表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_records', function (Blueprint $table): void {
            $table->id()->comment('退款ID');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->onDelete('cascade')
                ->comment('支付ID');
            $table->string('refund_no', 32)->unique()->comment('退款单号');
            $table->unsignedBigInteger('amount')->comment('退款金额 分');
            $table->string('reason', 200)->comment('退款原因');
            $table->unsignedTinyInteger('status')->default(0)
                ->comment('状态 0待审核1已通过2已拒绝3处理中4已完成');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('admins')
                ->onDelete('set null')
                ->comment('审批人');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->string('refund_path', 20)->default('original')->comment('退款路径 original/wallet/bank_card');
            $table->string('gateway_refund_no', 64)->nullable()->comment('网关退款流水号');
            $table->timestamp('completed_at')->nullable()->comment('退款完成时间');
            $table->timestamps();

            $table->index('order_id', 'idx_order_id');
            $table->index('payment_id', 'idx_payment_id');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_records');
    }
};
```

## 9. coupons 优惠券表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id()->comment('优惠券ID');
            $table->string('code', 20)->unique()->comment('优惠券码');
            $table->string('name', 50)->comment('名称');
            $table->unsignedTinyInteger('type')->comment('类型 1满减2折扣3免邮4直减');
            $table->decimal('value', 10, 2)->comment('面值/折扣率');
            $table->unsignedBigInteger('min_order_amount')->default(0)->comment('最低订单金额 分');
            $table->unsignedBigInteger('max_discount')->nullable()->comment('最大优惠金额 分');
            $table->unsignedInteger('total_quantity')->comment('总发行量');
            $table->unsignedInteger('used_quantity')->default(0)->comment('已使用量');
            $table->timestamp('start_at')->comment('生效时间');
            $table->timestamp('end_at')->comment('失效时间');
            $table->boolean('status')->default(true)->comment('状态');
            $table->json('scope')->nullable()->comment('适用范围JSON');
            $table->timestamps();

            $table->index('code', 'uk_code');
            $table->index('type', 'idx_type');
            $table->index('status', 'idx_status');
            $table->index('start_at', 'idx_start_at');
            $table->index('end_at', 'idx_end_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
```

## 10. customer_coupons 用户优惠券表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_coupons', function (Blueprint $table): void {
            $table->id()->comment('ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->foreignId('coupon_id')
                ->constrained('coupons')
                ->onDelete('cascade')
                ->comment('优惠券ID');
            $table->string('code', 20)->comment('优惠券码');
            $table->unsignedTinyInteger('status')->default(0)
                ->comment('状态 0未使用1已使用2已过期');
            $table->foreignId('used_order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null')
                ->comment('使用订单ID');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->timestamp('expired_at')->comment('过期时间');
            $table->timestamps();

            $table->index('customer_id', 'idx_customer_id');
            $table->index('coupon_id', 'idx_coupon_id');
            $table->index('status', 'idx_status');
            $table->index('expired_at', 'idx_expired_at');
            $table->unique(['customer_id', 'coupon_id'], 'uk_customer_coupon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_coupons');
    }
};
```

## 11. invoices 发票表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id()->comment('发票ID');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->string('invoice_no', 32)->unique()->comment('发票号');
            $table->unsignedTinyInteger('type')->comment('类型 1专票2普票');
            $table->unsignedTinyInteger('status')->default(0)
                ->comment('状态 0草稿1待审核2审核通过3已开具4已邮寄');
            $table->string('title', 100)->comment('发票抬头');
            $table->string('tax_number', 20)->comment('税号');
            $table->unsignedBigInteger('amount')->comment('发票金额 分');
            $table->string('email', 100)->nullable()->comment('接收邮箱');
            $table->string('address', 255)->nullable()->comment('邮寄地址');
            $table->string('bank_name', 50)->nullable()->comment('开户行');
            $table->string('bank_account', 30)->nullable()->comment('银行账号');
            $table->string('express_no', 50)->nullable()->comment('邮寄运单号');
            $table->timestamp('issued_at')->nullable()->comment('开具时间');
            $table->timestamps();

            $table->index('order_id', 'idx_order_id');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
```

## 12. after_sales 售后表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('after_sales', function (Blueprint $table): void {
            $table->id()->comment('售后ID');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade')
                ->comment('订单ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('restrict')
                ->comment('客户ID');
            $table->unsignedTinyInteger('type')->comment('类型 0退货退款1补印2优惠货款3其他');
            $table->string('reason', 200)->comment('原因');
            $table->text('description')->nullable()->comment('详细描述');
            $table->json('images')->nullable()->comment('凭证图片');
            $table->string('em_status', 20)->default('submitted')
                ->comment('EM状态 submitted/auditing/approved/rejected/completed');
            $table->string('af_status', 20)->default('submitted')
                ->comment('AF状态 submitted/factory_auditing/...');
            $table->unsignedBigInteger('amount')->default(0)->comment('售后金额 分');
            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('admins')
                ->onDelete('set null')
                ->comment('处理人');
            $table->timestamp('handled_at')->nullable()->comment('处理时间');
            $table->text('result')->nullable()->comment('处理结果');
            $table->timestamps();

            $table->index('order_id', 'idx_order_id');
            $table->index('em_status', 'idx_em_status');
            $table->index('af_status', 'idx_af_status');
            $table->index('customer_id', 'idx_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('after_sales');
    }
};
```

## 13. factories 印刷工厂表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factories', function (Blueprint $table): void {
            $table->id()->comment('工厂ID');
            $table->string('name', 100)->comment('工厂名称');
            $table->string('code', 10)->unique()->comment('工厂编码');
            $table->string('address', 255)->comment('地址');
            $table->string('contact_name', 50)->comment('联系人');
            $table->string('contact_phone', 20)->comment('联系电话');
            $table->unsignedInteger('capacity_daily')->comment('日产能');
            $table->json('supported_products')->nullable()->comment('支持产品类型');
            $table->json('equipment')->nullable()->comment('设备列表');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0停用1启用');
            $table->timestamps();

            $table->index('code', 'uk_code');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factories');
    }
};
```

## 14. shipping_templates 运费模板表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_templates', function (Blueprint $table): void {
            $table->id()->comment('模板ID');
            $table->foreignId('factory_id')
                ->constrained('factories')
                ->onDelete('cascade')
                ->comment('工厂ID');
            $table->string('name', 50)->comment('模板名称');
            $table->unsignedInteger('default_first_weight')->comment('首重 g');
            $table->unsignedBigInteger('default_first_price')->comment('首重价格 分');
            $table->unsignedInteger('default_continue_weight')->comment('续重 g');
            $table->unsignedBigInteger('default_continue_price')->comment('续重价格 分');
            $table->boolean('is_default')->default(false)->comment('是否默认');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态');
            $table->json('region_rules')->nullable()->comment('区域规则JSON');
            $table->timestamps();

            $table->index('factory_id', 'idx_factory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_templates');
    }
};
```

## 15. notifications 通知表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id()->comment('通知ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->string('type', 20)->comment('类型 order/payment/logistics/system/marketing');
            $table->string('title', 100)->comment('标题');
            $table->text('content')->comment('内容');
            $table->boolean('is_read')->default(false)->comment('是否已读');
            $table->string('link', 500)->nullable()->comment('跳转链接');
            $table->json('extra')->nullable()->comment('扩展数据');
            $table->timestamps();

            $table->index('customer_id', 'idx_customer_id');
            $table->index(['customer_id', 'is_read'], 'idx_customer_read');
            $table->index('type', 'idx_type');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

## 16. admins 管理员表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->id()->comment('管理员ID');
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('password', 255)->comment('密码');
            $table->string('real_name', 50)->comment('真实姓名');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('role', 30)->comment('角色 super_admin/operator/customer_service/factory_manager/finance');
            $table->foreignId('factory_id')
                ->nullable()
                ->constrained('factories')
                ->onDelete('set null')
                ->comment('关联工厂（工厂经理）');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0禁用1启用');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->rememberToken();
            $table->timestamps();

            $table->index('role', 'idx_role');
            $table->index('status', 'idx_status');
            $table->index('factory_id', 'idx_factory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
```

## 17. audit_logs 审计日志表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id()->comment('日志ID');
            $table->unsignedBigInteger('user_id')->nullable()->comment('操作者ID');
            $table->string('user_type', 20)->default('customer')->comment('用户类型 customer/admin/system');
            $table->string('action', 50)->comment('操作动作');
            $table->string('target_type', 50)->comment('目标类型 Order/Payment/...');
            $table->unsignedBigInteger('target_id')->comment('目标ID');
            $table->json('old_values')->nullable()->comment('变更前值');
            $table->json('new_values')->nullable()->comment('变更后值');
            $table->string('ip', 45)->comment('IP地址');
            $table->string('user_agent', 500)->nullable()->comment('UA');
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'idx_user_id');
            $table->index(['target_type', 'target_id'], 'idx_target');
            $table->index('action', 'idx_action');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

## 18. customer_points 积分明细表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_points', function (Blueprint $table): void {
            $table->id()->comment('积分记录ID');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('客户ID');
            $table->integer('amount')->comment('积分数量 正=获得 负=使用');
            $table->unsignedTinyInteger('type')->comment('类型 1消费2评价3退款扣减4过期5签到');
            $table->string('source', 100)->comment('来源描述');
            $table->unsignedBigInteger('source_id')->nullable()->comment('来源ID');
            $table->string('source_type', 50)->nullable()->comment('来源类型 Order/Review/...');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->boolean('is_expired')->default(false)->comment('是否已过期');
            $table->timestamps();

            $table->index('customer_id', 'idx_customer_id');
            $table->index(['customer_id', 'expired_at'], 'idx_customer_expired');
            $table->index('type', 'idx_type');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_points');
    }
};
```

## 19. product_inventories 库存表

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_inventories', function (Blueprint $table): void {
            $table->id()->comment('库存ID');
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('商品ID');
            $table->foreignId('factory_id')
                ->nullable()
                ->constrained('factories')
                ->onDelete('set null')
                ->comment('工厂ID');
            $table->unsignedInteger('stock')->default(0)->comment('可用库存');
            $table->unsignedInteger('reserved')->default(0)->comment('预占库存');
            $table->unsignedInteger('warning_threshold')->default(100)->comment('预警阈值');
            $table->timestamps();

            $table->index('product_id', 'idx_product_id');
            $table->index('factory_id', 'idx_factory_id');
            $table->unique(['product_id', 'factory_id'], 'uk_product_factory');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventories');
    }
};
```

---

*19个核心表Migration全部定义完成，包含完整的外键约束、索引、注释，开发可直接复制到 `database/migrations/` 目录执行 `php artisan migrate`。*
