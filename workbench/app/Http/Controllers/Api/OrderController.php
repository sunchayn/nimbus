<?php

namespace Workbench\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Workbench\App\Http\Requests\Api\StoreOrderRequest;
use Workbench\App\Http\Requests\Api\UpdateOrderStatusRequest;
use Workbench\App\Http\Resources\OrderResource;
use Workbench\App\Models\Order;
use Workbench\App\Models\Product;

class OrderController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::with('items')->where('user_id', $request->user()->id)->latest('id')->get()
        );
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($order->user_id === $request->user()->id, 403, 'This order belongs to another user.');

        return new OrderResource($order->load('items'));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'payment_method' => $request->validated('payment_method'),
                'shipping_address' => $request->validated('shipping_address'),
                'notes' => $request->validated('notes'),
            ]);

            $total = 0;

            foreach ($request->validated('items') as $item) {
                $price = Product::findOrFail($item['product_id'])->price;
                $order->items()->create([...$item, 'price' => $price]);
                $total += $price * $item['quantity'];
            }

            $order->update(['total' => $total]);

            return $order;
        });

        return (new OrderResource($order->load('items')))->response()->setStatusCode(201);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $order->update($request->validated());

        return new OrderResource($order->load('items'));
    }
}
