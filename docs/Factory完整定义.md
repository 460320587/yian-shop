# 怡安印刷商城 — Factory完整定义（Factory Definitions）

> **版本**: v1.0  
> **日期**: 2026-05-30  
> **用途**: 开发可直接复制到 `database/factories/` 的完整Factory定义  
> **技术栈**: Laravel 13.x Factory + Faker PHP

---

## 目录

1. [UserFactory](#1-userfactory)
2. [CustomerFactory](#2-customerfactory)
3. [CustomerAddressFactory](#3-customeraddressfactory)
4. [ProductFactory](#4-productfactory)
5. [ProductCategoryFactory](#5-productcategoryfactory)
6. [OrderFactory](#6-orderfactory)
7. [OrderItemFactory](#7-orderitemfactory)
8. [PaymentFactory](#8-paymentfactory)
9. [RefundRecordFactory](#9-refundrecordfactory)
10. [CouponFactory](#10-couponfactory)
11. [NotificationFactory](#11-notificationfactory)
12. [BannerFactory](#12-bannerfactory)
13. [ArticleFactory](#13-articlefactory)
14. [AdminFactory](#14-adminfactory)
15. [FactoryFactory](#15-factoryfactory)
16. [ShippingTemplateFactory](#16-shippingtemplatefactory)
17. [InvoiceFactory](#17-invoicefactory)
18. [AfterSaleFactory](#18-aftersalefactory)

---

## 1. UserFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'phone' => $this->faker->unique()->regexify('1[3-9]\d{9}'),
            'password' => bcrypt('Test@1234'),
            'nickname' => $this->faker->name(),
            'avatar' => $this->faker->imageUrl(200, 200, 'people'),
            'customer_type' => $this->faker->randomElement([3, 4, 5, 6, 7, 8]),
            'vip_level' => $this->faker->numberBetween(0, 8),
            'status' => 1,
            'email' => $this->faker->unique()->safeEmail(),
            'last_login_at' => now(),
            'last_login_ip' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 4,
            'company_name' => $this->faker->company(),
        ]);
    }

    public function blacklisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
            'risk_score' => 85,
        ]);
    }
}
```

## 2. CustomerFactory

（已包含在UserFactory中，Customer = User）

## 3. CustomerAddressFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Customers\Models\CustomerAddress;
use App\Domains\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'contact_name' => $this->faker->name(),
            'phone' => $this->faker->regexify('1[3-9]\d{9}'),
            'province' => $this->faker->state(),
            'city' => $this->faker->city(),
            'district' => $this->faker->district(),
            'detail_address' => $this->faker->streetAddress(),
            'zip_code' => $this->faker->postcode(),
            'is_default' => false,
            'label' => $this->faker->randomElement(['家', '公司', '工厂']),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
```

## 4. ProductFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Products\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'name' => $this->faker->randomElement([
                'A4企业宣传册', '铜版纸名片', 'A2海报', '精装画册',
                '手提袋', '不干胶标签', '包装盒', '台历',
            ]),
            'subtitle' => $this->faker->sentence(10),
            'description' => $this->faker->paragraphs(3, true),
            'images' => array_map(
                fn () => $this->faker->imageUrl(800, 600, 'business'),
                range(1, 5)
            ),
            'status' => ProductStatus::OnSale->value,
            'sort' => $this->faker->numberBetween(0, 100),
            'is_recommended' => $this->faker->boolean(20),
            'pricing_params' => [
                'paper_types' => ['157g铜版纸', '200g铜版纸', '250g白卡纸'],
                'color_modes' => ['单色', '双色', '四色'],
                'finishing' => ['覆膜', '烫金', 'UV', '模切'],
                'min_quantity' => 100,
                'max_quantity' => 100000,
            ],
            'specifications' => [
                'finished_size' => '210x285mm',
                'bleed' => '3mm',
                'resolution' => '300dpi',
                'color_mode' => 'CMYK',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Draft->value,
        ]);
    }

    public function offShelf(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::OffShelf->value,
        ]);
    }
}
```

## 5. ProductCategoryFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Products\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'parent_id' => 0,
            'name' => $this->faker->randomElement([
                '宣传册', '名片', '海报', '画册', '包装', '标签', '台历', '纸袋',
            ]),
            'icon' => $this->faker->emoji(),
            'sort' => $this->faker->numberBetween(0, 100),
            'is_show' => true,
            'level' => 1,
            'path' => '',
        ];
    }

    public function child(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
            'level' => 2,
            'name' => $this->faker->randomElement(['铜版纸', '哑粉纸', '白卡纸']),
        ]);
    }
}
```

## 6. OrderFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

final class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(5000_00, 500000_00);

        return [
            'order_no' => 'Y' . now()->format('Ymd') . str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'address_id' => CustomerAddress::factory(),
            'factory_id' => null,
            'status' => OrderStatus::PendingConfirmation->value,
            'order_type' => $this->faker->randomElement([1, 2, 3, 4]),
            'total_amount' => $amount,
            'paid_amount' => 0,
            'discount_amount' => 0,
            'coupon_code' => null,
            'use_points' => false,
            'points_used' => 0,
            'use_balance' => false,
            'balance_used' => 0,
            'address_snapshot' => [
                'contact_name' => $this->faker->name(),
                'phone' => $this->faker->regexify('1[3-9]\d{9}'),
                'full_address' => $this->faker->address(),
            ],
            'invoice_info' => null,
            'remark' => $this->faker->optional()->sentence(),
            'deadline' => $this->faker->dateTimeBetween('+3 days', '+30 days'),
            'is_urgent' => false,
            'urgent_days' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Paid->value,
                'paid_amount' => $attributes['total_amount'],
            ];
        });
    }

    public function shipped(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Shipped->value,
                'paid_amount' => $attributes['total_amount'],
                'express_no' => 'SF' . $this->faker->numerify('###########'),
                'express_company' => '顺丰速运',
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::AwaitingReceipt->value,
                'paid_amount' => $attributes['total_amount'],
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled->value,
        ]);
    }
}
```

## 7. OrderItemFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Orders\Models\OrderItem;
use App\Domains\Orders\Models\Order;
use App\Domains\Products\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(100, 10000);
        $unitPrice = $this->faker->numberBetween(50, 500);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => $this->faker->randomElement(['A4企业宣传册', '铜版纸名片']),
            'product_image' => $this->faker->imageUrl(200, 200),
            'specs' => [
                '纸张' => '157g铜版纸',
                '色数' => '四色',
                '工艺' => '覆膜',
                '尺寸' => '210x285mm',
            ],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'factory_id' => null,
            'is_urgent' => false,
        ];
    }
}
```

## 8. PaymentFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => fn (array $attributes) => Order::find($attributes['order_id'])->customer_id,
            'payment_no' => 'P' . now()->format('YmdHis') . $this->faker->unique()->numerify('######'),
            'amount' => $this->faker->numberBetween(5000_00, 500000_00),
            'pay_type' => $this->faker->randomElement([1, 3, 8]),
            'status' => PaymentStatus::Pending->value,
            'gateway_trade_no' => null,
            'paid_at' => null,
            'refunded_amount' => 0,
            'created_at' => now(),
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Success->value,
            'gateway_trade_no' => $this->faker->uuid(),
            'paid_at' => now(),
        ]);
    }
}
```

## 9. RefundRecordFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Payments\Models\RefundRecord;
use App\Domains\Payments\Models\Payment;
use App\Domains\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RefundRecordFactory extends Factory
{
    protected $model = RefundRecord::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'refund_no' => 'R' . now()->format('YmdHis') . $this->faker->unique()->numerify('######'),
            'amount' => $this->faker->numberBetween(1000_00, 50000_00),
            'reason' => $this->faker->randomElement(['质量问题', '错印', '漏印', '客户取消']),
            'status' => $this->faker->randomElement(['pending', 'approved', 'completed', 'rejected']),
            'approved_by' => null,
            'approved_at' => null,
            'refund_path' => $this->faker->randomElement(['original', 'wallet', 'bank_card']),
            'created_at' => now(),
        ];
    }
}
```

## 10. CouponFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Marketing\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([1, 2, 3, 4]);

        return [
            'code' => strtoupper($this->faker->unique()->bothify('YIAN####')),
            'name' => $this->faker->randomElement(['新人礼包', '满减券', '折扣券', '免邮券']),
            'type' => $type,
            'value' => match ($type) {
                1 => $this->faker->numberBetween(1000_00, 10000_00),   // 满减: 减多少
                2 => $this->faker->randomFloat(2, 0.5, 0.9),          // 折扣: 折扣率
                3 => $this->faker->numberBetween(500, 3000),           // 免邮: 最高免邮额
                4 => $this->faker->numberBetween(500_00, 5000_00),    // 直减: 减多少
            },
            'min_order_amount' => $this->faker->numberBetween(1000_00, 100000_00),
            'max_discount' => $type === 2 ? $this->faker->numberBetween(5000_00, 50000_00) : null,
            'total_quantity' => $this->faker->numberBetween(100, 10000),
            'used_quantity' => 0,
            'start_at' => now(),
            'end_at' => $this->faker->dateTimeBetween('+7 days', '+90 days'),
            'status' => 1,
        ];
    }
}
```

## 11. NotificationFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Notifications\Models\Notification;
use App\Domains\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

final class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => $this->faker->randomElement(['order', 'payment', 'logistics', 'system', 'marketing']),
            'title' => $this->faker->randomElement([
                '订单已发货', '支付成功', '退款已到账', '新优惠券到账',
            ]),
            'content' => $this->faker->sentence(20),
            'is_read' => $this->faker->boolean(30),
            'link' => $this->faker->optional()->url(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
```

## 12. BannerFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Content\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(5),
            'image' => $this->faker->imageUrl(1920, 600, 'business'),
            'link' => $this->faker->url(),
            'position' => $this->faker->randomElement(['home_top', 'home_middle', 'category']),
            'sort' => $this->faker->numberBetween(0, 100),
            'is_show' => true,
            'start_at' => now(),
            'end_at' => $this->faker->dateTimeBetween('+30 days', '+90 days'),
        ];
    }
}
```

## 13. ArticleFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Content\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(8),
            'summary' => $this->faker->paragraph(2),
            'content' => $this->faker->paragraphs(10, true),
            'cover' => $this->faker->imageUrl(800, 400, 'business'),
            'category' => $this->faker->randomElement(['help', 'news', 'guide']),
            'is_top' => $this->faker->boolean(10),
            'sort' => $this->faker->numberBetween(0, 100),
            'status' => 1,
            'view_count' => $this->faker->numberBetween(0, 10000),
        ];
    }
}
```

## 14. AdminFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

final class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => bcrypt('Admin@1234'),
            'real_name' => $this->faker->name(),
            'phone' => $this->faker->regexify('1[3-9]\d{9}'),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => $this->faker->randomElement(['operator', 'customer_service', 'factory_manager', 'finance']),
            'factory_id' => null,
            'status' => 1,
            'last_login_at' => now(),
            'last_login_ip' => $this->faker->ipv4(),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => 'admin' . $this->faker->unique()->numerify('###'),
            'role' => 'super_admin',
        ]);
    }

    public function factoryManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'factory_manager',
            'factory_id' => $this->faker->numberBetween(1, 4),
        ]);
    }
}
```

## 15. FactoryFactory（印刷工厂）

```php
<?php

namespace Database\Factories;

use App\Domains\Factories\Models\Factory as FactoryModel;
use Illuminate\Database\Eloquent\Factories\Factory;

final class FactoryFactory extends Factory
{
    protected $model = FactoryModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                '上海永城印刷厂', '成都天成印刷', '天水华兴印务', '天津宏达印刷',
            ]),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}'),
            'address' => $this->faker->address(),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->regexify('1[3-9]\d{9}'),
            'capacity_daily' => $this->faker->numberBetween(10000, 100000),
            'status' => 1,
        ];
    }
}
```

## 16. ShippingTemplateFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Logistics\Models\ShippingTemplate;
use App\Domains\Factories\Models\Factory;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ShippingTemplateFactory extends Factory
{
    protected $model = ShippingTemplate::class;

    public function definition(): array
    {
        return [
            'factory_id' => Factory::factory(),
            'name' => $this->faker->randomElement(['标准快递', '顺丰特快', '物流专线']),
            'default_first_weight' => 1000,      // g
            'default_first_price' => 1200,       // 分, ¥12
            'default_continue_weight' => 1000,   // g
            'default_continue_price' => 500,     // 分, ¥5
            'is_default' => false,
            'status' => 1,
        ];
    }
}
```

## 17. InvoiceFactory

```php
<?php

namespace Database\Factories;

use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Enums\InvoiceStatus;
use App\Domains\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

final class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => fn (array $attributes) => Order::find($attributes['order_id'])->customer_id,
            'invoice_no' => 'INV' . now()->format('Ymd') . $this->faker->unique()->numerify('######'),
            'type' => $this->faker->randomElement([1, 2]),  // 1专票 2普票
            'status' => InvoiceStatus::Draft->value,
            'title' => $this->faker->company(),
            'tax_number' => $this->faker->regexify('[A-Z0-9]{15,20}'),
            'amount' => $this->faker->numberBetween(1000_00, 100000_00),
            'email' => $this->faker->safeEmail(),
            'address' => $this->faker->address(),
            'bank_name' => $this->faker->randomElement(['工商银行', '建设银行', '招商银行']),
            'bank_account' => $this->faker->bankAccountNumber(),
            'created_at' => now(),
        ];
    }
}
```

## 18. AfterSaleFactory

```php
<?php

namespace Database\Factories;

use App\Domains\AfterSales\Models\AfterSale;
use App\Domains\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

final class AfterSaleFactory extends Factory
{
    protected $model = AfterSale::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => fn (array $attributes) => Order::find($attributes['order_id'])->customer_id,
            'type' => $this->faker->randomElement([0, 1, 2, 3]),  // 退货退款/补印/优惠货款/其他
            'reason' => $this->faker->randomElement(['质量问题', '错印', '漏印', '色差']),
            'description' => $this->faker->paragraph(),
            'images' => array_map(
                fn () => $this->faker->imageUrl(800, 600),
                range(1, 3)
            ),
            'em_status' => 'submitted',
            'af_status' => 'submitted',
            'amount' => $this->faker->numberBetween(1000_00, 50000_00),
            'created_at' => now(),
        ];
    }
}
```

---

*18个Factory全部定义完成，覆盖用户/商品/订单/支付/退款/优惠券/通知/内容/后台/工厂/物流/发票/售后全链路，开发可直接复制到 `database/factories/` 目录。*
