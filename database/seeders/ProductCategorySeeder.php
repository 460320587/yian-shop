<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Product\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '名片', 'icon' => '/images/categories/card.png', 'sort' => 10, 'level' => 1, 'path' => '1'],
            ['name' => '画册', 'icon' => '/images/categories/brochure.png', 'sort' => 20, 'level' => 1, 'path' => '2'],
            ['name' => '包装盒', 'icon' => '/images/categories/box.png', 'sort' => 30, 'level' => 1, 'path' => '3'],
            ['name' => '标签', 'icon' => '/images/categories/label.png', 'sort' => 40, 'level' => 1, 'path' => '4'],
            ['name' => '广告物料', 'icon' => '/images/categories/ad.png', 'sort' => 50, 'level' => 1, 'path' => '5'],
        ];

        foreach ($categories as $data) {
            ProductCategory::create($data);
        }
    }
}
