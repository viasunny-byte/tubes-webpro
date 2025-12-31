<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Surfsidemedia\Shoppingcart\Facades\Cart;

class WishlistController extends Controller
{
    // GET /api/wishlist
    public function index()
    {
        return response()->json([
            'items' => Cart::instance('wishlist')->content()->values()
        ]);
    }

    // POST /api/wishlist/add
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'name'       => 'required',
            'price'      => 'required|numeric',
            'qty'        => 'nullable|integer|min:1'
        ]);

        Cart::instance('wishlist')->add(
            $request->product_id,
            $request->name,
            $request->qty ?? 1,
            $request->price
        )->associate('App\Models\Product');

        return response()->json([
            'message' => 'Item added to wishlist',
            'items'   => Cart::instance('wishlist')->content()->values()
        ]);
    }

    // DELETE /api/wishlist/{rowId}
    public function remove($rowId)
    {
        Cart::instance('wishlist')->remove($rowId);

        return response()->json([
            'message' => 'Item removed from wishlist'
        ]);
    }

    // DELETE /api/wishlist
    public function clear()
    {
        Cart::instance('wishlist')->destroy();

        return response()->json([
            'message' => 'Wishlist cleared'
        ]);
    }

    // POST /api/wishlist/move-to-cart/{rowId}
    public function moveToCart($rowId)
    {
        $item = Cart::instance('wishlist')->get($rowId);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        Cart::instance('wishlist')->remove($rowId);

        Cart::instance('cart')->add(
            $item->id,
            $item->name,
            $item->qty,
            $item->price
        )->associate('App\Models\Product');

        return response()->json([
            'message' => 'Item moved to cart',
            'cart'    => Cart::instance('cart')->content()->values()
        ]);
    }
}