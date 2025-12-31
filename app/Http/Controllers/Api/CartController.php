<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use App\Models\Coupon;
use Carbon\Carbon;

class CartController extends Controller
{
    // GET /api/cart
    public function index()
    {
        return response()->json([
            'items' => Cart::instance('cart')->content()->values(),
            'summary' => $this->summary()
        ]);
    }

    // POST /api/cart/add
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'name'       => 'required',
            'price'      => 'required|numeric',
            'qty'        => 'nullable|integer|min:1'
        ]);

        Cart::instance('cart')->add(
            $request->product_id,
            $request->name,
            $request->qty ?? 1,
            $request->price
        )->associate('App\Models\Product');

        return response()->json([
            'message' => 'Item added to cart',
            'cart' => Cart::instance('cart')->content()->values()
        ]);
    }

    // PUT /api/cart/increase/{rowId}
    public function increase($rowId)
    {
        $item = Cart::instance('cart')->get($rowId);
        Cart::instance('cart')->update($rowId, $item->qty + 1);

        return response()->json(['message' => 'Quantity increased']);
    }

    // PUT /api/cart/decrease/{rowId}
    public function decrease($rowId)
    {
        $item = Cart::instance('cart')->get($rowId);
        $qty = max(1, $item->qty - 1);
        Cart::instance('cart')->update($rowId, $qty);

        return response()->json(['message' => 'Quantity decreased']);
    }

    // DELETE /api/cart/{rowId}
    public function remove($rowId)
    {
        Cart::instance('cart')->remove($rowId);

        return response()->json(['message' => 'Item removed']);
    }

    // DELETE /api/cart
    public function clear()
    {
        Cart::instance('cart')->destroy();

        return response()->json(['message' => 'Cart cleared']);
    }

    // POST /api/cart/apply-coupon
    public function applyCoupon(Request $request)
    {
        $request->validate(['coupon_code' => 'required']);

        $subtotal = Cart::instance('cart')->subtotalFloat();

        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('expiry_date', '>=', Carbon::today())
            ->where('cart_value', '<=', $subtotal)
            ->first();

        if (!$coupon) {
            return response()->json(['message' => 'Invalid coupon'], 400);
        }

        session()->put('coupon', [
            'code'  => $coupon->code,
            'type'  => $coupon->type,
            'value' => $coupon->value,
        ]);

        return response()->json([
            'message' => 'Coupon applied',
            'summary' => $this->summary()
        ]);
    }

    // DELETE /api/cart/coupon
    public function removeCoupon()
    {
        session()->forget(['coupon', 'discounts']);

        return response()->json(['message' => 'Coupon removed']);
    }

    // =====================
    // HELPER
    // =====================
    private function summary()
    {
        $subtotal = Cart::instance('cart')->subtotalFloat();
        $tax = Cart::instance('cart')->taxFloat();
        $total = Cart::instance('cart')->totalFloat();
        $discount = 0;

        if (session()->has('coupon')) {
            $coupon = session('coupon');
            $discount = $coupon['type'] === 'fixed'
                ? $coupon['value']
                : ($subtotal * $coupon['value']) / 100;

            $subtotal -= $discount;
            $tax = ($subtotal * config('cart.tax')) / 100;
            $total = $subtotal + $tax;
        }

        return compact('subtotal', 'discount', 'tax', 'total');
    }
}