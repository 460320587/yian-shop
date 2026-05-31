<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ProductCategory::all();

        if ($categories->isEmpty()) {
            return;
        }

        foreach ($categories as $category) {
            Product::factory()->count(3)->create([
                'category_id' => $category->id,
                'status' => 1,
            ]);
        }
    }
}
