<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Workbench\App\Http\Requests\Demo\StoreDemoOrderRequest;
use Workbench\App\Http\Resources\OrderResource;
use Workbench\App\Models\Order;
use Workbench\App\Models\Product;

class DemoOrderController
{
    /**
     * Create an order with one item, to show an exists rule on a top level field.
     */
    public function store(StoreDemoOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request) {
            $price = Product::findOrFail($request->validated('product_id'))->price;
            $quantity = $request->validated('quantity');

            $order = Order::create([
                'user_id' => $request->user()->id,
                'payment_method' => $request->validated('payment_method'),
                'shipping_address' => $request->validated('shipping_address'),
                'notes' => $request->validated('notes'),
                'total' => $price * $quantity,
            ]);

            $order->items()->create([
                'product_id' => $request->validated('product_id'),
                'quantity' => $quantity,
                'price' => $price,
            ]);

            return $order;
        });

        return (new OrderResource($order->load('items')))->response()->setStatusCode(201);
    }
}
