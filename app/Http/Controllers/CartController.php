<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;


class CartController extends Controller
{
    public function index()
    {
        $items = Cart::instance('cart')->content();
        return view('cart', compact('items'));
    }

    public function add_to_cart(Request $request)
    {
        Cart::instance('cart')->add($request->id, $request->name, 1, $request->price)->associate('App\Models\Product');
        return redirect()->back();
    }

    public function increase_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function decrease_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function remove_item($rowId)
    {
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();
    }

    public function empty_cart()
    {
        Cart::instance('cart')->destroy();
        return redirect()->back();  
    }

    public function apply_coupon_code(Request $request){        
    $coupon_code = $request->coupon_code;
    if(isset($coupon_code)){
        $cartSubtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));

    $coupon = Coupon::where('code', $coupon_code)
        ->where('expiry_date', '>=', Carbon::today())
        ->where('cart_value', '<=', $cartSubtotal)
        ->first();

        if(!$coupon)
        {
            return back()->with('error','Invalid coupon code!');
        }
        session()->put('coupon',[
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'cart_value' => $coupon->cart_value
        ]);
        $this->calculateDiscounts();
        return back()->with('success','Coupon code has been applied!');
        }
        else{
            return back()->with('error','Invalid coupon code!');
        }        
    }

    public function calculateDiscounts(){
    $discount = 0;

    if(session()->has('coupon')){
        $cartSubtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));

        if(session()->get('coupon')['type'] == 'fixed'){
            $discount = session()->get('coupon')['value'];
        }
        else{
            $discount = ($cartSubtotal * session()->get('coupon')['value']) / 100;
        }

        $subtotalAfterDiscount = $cartSubtotal - $discount;
        $taxAfterDiscount = ($subtotalAfterDiscount * config('cart.tax')) / 100;
        $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

        session()->put('discounts',[
            'discount' => (float) $discount,
            'subtotal' => (float) $subtotalAfterDiscount,
            'tax'      => (float) $taxAfterDiscount,
            'total'    => (float) $totalAfterDiscount,
        ]);
    }
    }

    public function remove_coupon_code()
    {
        session()->forget('coupon');
        session()->forget('discounts');
        return back()->with('success','Coupon has been removed!');
    }

    public function checkout()
    {
        if(!Auth::check())
        {
            return redirect()->route('login');
        }
        $address = Address::where('user_id',Auth::user()->id)->where('isdefault',1)->first();              
        return view('checkout', compact('address'));
    }
    
    public function place_order(Request $request)
    {
        $user_id = Auth::user()->id;

        $address = Address::where('user_id', $user_id)
            ->where('isdefault', true)
            ->first();

        if (!$address) {
            $request->validate([
                'name' => 'required|max:100',
                'phone' => 'required|numeric|digits_between:10,15',
                'zip' => 'required|numeric|digits:5',
                'state' => 'required',
                'city' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'landmark' => 'required',
            ]);

            $address = new Address();
            $address->user_id = $user_id;
            $address->name = $request->name;
            $address->phone = $request->phone;
            $address->zip = $request->zip;
            $address->state = $request->state;
            $address->city = $request->city;
            $address->address = $request->address;
            $address->locality = $request->locality;
            $address->landmark = $request->landmark;
            $address->country = 'Indonesia';
            $address->isdefault = true;
            $address->save();
        }

        $this->setAmountForCheckout();
        if (!session()->has('checkout')) {
            return redirect()->route('cart.checkout')
                ->with('error', 'Checkout data tidak ditemukan. Coba checkout ulang.');
        }

        $order = new Order();
        $order->user_id = $user_id;
        $order->subtotal = session('checkout.subtotal');
        $order->discount = session('checkout.discount');
        $order->tax = session('checkout.tax');
        $order->total = session('checkout.total');
        $order->name = $address->name;
        $order->phone = $address->phone;
        $order->locality = $address->locality;
        $order->address = $address->address;
        $order->city = $address->city;
        $order->state = $address->state;
        $order->country = $address->country;
        $order->landmark = $address->landmark;
        $order->zip = $address->zip;
        $order->save();

        foreach (Cart::instance('cart')->content() as $item) {
            $orderitem = new OrderItem();
            $orderitem->product_id = $item->id;
            $orderitem->order_id = $order->id;
            $orderitem->price = $item->price;
            $orderitem->quantity = $item->qty;
            $orderitem->save();
        }
        if($request->mode == 'card'){
            //
        }elseif($request->mode == 'paypal'){
            //
        }elseif($request->mode == 'cod'){
        $transaction = new Transaction();
        $transaction->user_id = $user_id;
        $transaction->order_id = $order->id;
        $transaction->mode = $request->mode;
        $transaction->status = 'pending';
        $transaction->save();
        }

        Cart::instance('cart')->destroy();
        session()->forget(['checkout', 'coupon', 'discounts']);
        session()->put('order_id', $order->id);
        return redirect()->route('cart.confirmation');

    }

    public function setAmountForCheckout()
    {
        if (Cart::instance('cart')->count() <= 0) {
            session()->forget('checkout');
            return;
        }

        if (session()->has('discounts')) {
            session()->put('checkout', [
                'discount' => session('discounts.discount'),
                'subtotal' => session('discounts.subtotal'),
                'tax' => session('discounts.tax'),
                'total' => session('discounts.total'),
            ]);
        } else {
            $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));
            $tax      = floatval(str_replace(',', '', Cart::instance('cart')->tax()));
            $total    = floatval(str_replace(',', '', Cart::instance('cart')->total()));

            session()->put('checkout', [
                'discount' => 0,
                'subtotal' => $subtotal,
                'tax'      => $tax,
                'total'    => $total,
            ]);
        }
    }

    public function confirmation()
    {
        if (Session::has('order_id')) {
            $order = Order::find(Session::get('order_id'));
            return view('order-confirmation', compact('order'));
        }
        return redirect()->route('cart.index');
    }

}
