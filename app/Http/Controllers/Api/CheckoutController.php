<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\Address;

class CheckoutController extends Controller
{
    public function summary()
    {
        return response()->json([
            'items' => Cart::instance('cart')->content()->values(),
            'subtotal' => Cart::instance('cart')->subtotal(),
            'tax' => Cart::instance('cart')->tax(),
            'total' => Cart::instance('cart')->total(),
        ]);
    }

    public function placeOrder(Request $request)
    {
        if (Cart::instance('cart')->count() === 0) {
            return response()->json(['message' => 'Cart empty'], 422);
        }

        $user = Auth::user();

        $address = Address::where('user_id', $user->id)
            ->where('isdefault', true)
            ->first();

        if (!$address) {
            return response()->json([
                'message' => 'Default address not found'
            ], 422);
        }

        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => Cart::instance('cart')->subtotal(),
            'tax' => Cart::instance('cart')->tax(),
            'total' => Cart::instance('cart')->total(),
            'name' => $address->name,
            'phone' => $address->phone,
            'address' => $address->address,
            'city' => $address->city,
            'state' => $address->state,
            'zip' => $address->zip,
            'country' => $address->country,
        ]);

        foreach (Cart::instance('cart')->content() as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->id,
                'price' => $item->price,
                'quantity' => $item->qty,
            ]);
        }

        Transaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'mode' => 'cod',
            'status' => 'pending',
        ]);

        Cart::instance('cart')->destroy();

        return response()->json([
            'message' => 'Order placed',
            'order_id' => $order->id,
        ]);
    }
}