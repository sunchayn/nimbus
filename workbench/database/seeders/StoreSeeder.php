<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Category;
use Workbench\App\Models\Order;
use Workbench\App\Models\Product;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Books', 'Electronics', 'Garden', 'Toys'] as $name) {
            $category = Category::create(['name' => $name, 'slug' => strtolower($name)]);

            foreach (range(1, 4) as $i) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => "$name item $i",
                    'description' => $i === 4 ? null : "A $name product number $i.",
                    'price' => $i * 12.5,
                    'status' => ['active', 'active', 'inactive', 'discontinued'][$i - 1],
                    'stock_quantity' => $i * 10,
                    'sku' => strtoupper(substr($name, 0, 3))."-$i",
                    'is_featured' => $i === 1,
                    'tags' => ['sample', strtolower($name)],
                    'specifications' => ['weight_kg' => $i, 'color' => 'blue'],
                ]);
            }
        }

        foreach ([1, 2, 3] as $userId) {
            $order = Order::create([
                'user_id' => $userId,
                'status' => ['pending', 'shipped', 'delivered'][$userId - 1],
                'payment_method' => 'credit_card',
                'shipping_address' => ['street' => '1 Main St', 'city' => 'Springfield', 'country' => 'US'],
            ]);
            $order->items()->create(['product_id' => $userId, 'price' => 12.5, 'quantity' => 2]);
            $order->update(['total' => 25]);
        }
    }
}
