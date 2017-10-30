<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BaklySystems\PayMob\Facades\PayMob;

class PayMobController extends Controller
{

    /**
     * Load cart checkout detilas with specific branch.
     *
     * @param /Illuminate/Http/Request $request
     * @return Response
     */
    public function placeOrder(Request $request)
    {
        $this->validate($request, [
            'fname' => 'required',
            'lname' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'branchId' => 'required'
        ]);

        // Installer
        $installer = Installer::find($request->installer);

        // Add installation price to cart session
        SessionCart::add(
            $installer->id, // installer id
            $installer, // installer object
            1, // quantity
            $request->installation_price, // installation price
            ['type' => 'installer'] // type as installer to differentiate between installer and tires
        );

        // Will make fake account and login with it
        User::userFakeLogin(
            $request->email,
            $request->fname,
            $request->lname,
            $request->phone
        );

        // Add items into users cart
        $cart = Cart::current();
        $items = SessionCart::content(); // get items from session cart
        $installer_item = '';
        foreach ($items as $item) {
            if (!$item->options->type) {
                $cart->add($item->name, $item->qty);
            } else {
                $installer_item = $item;
            }
        }
        $totalPrice = $cart->totalPrice;

        $installprice = $cart->OrderInstallPrice($request->branchId);

        $order = $cart->placeOrder("unpaid");
        $order->branch_id = $request->branchId;
        $order->totalPrice = $totalPrice;
        $order->totalCost = $totalPrice + $installprice;
        $order->installPrice = $installprice;
        $order->save();

        $auth = $this->authPaymob(); // login paymob servers

        if (property_exists($auth, 'detail')) {
            SessionCart::destroy();
            return redirect('tires');
        }
        $paymob_order = $this->makeOrderPaymob( // register order
            $auth->token,
            $auth->profile->id,
            $order->totalCost * 100,
            $order->id
        );
        // Duplicate order id
        if (isset($paymob_order->message)) {
            if ($paymob_order->message == 'duplicate') {
                SessionCart::destroy();
                return redirect('tires');
            }
        }
        // Save paymob order id.
        $order->paymob_order_id = $paymob_order->id;
        $order->save();

        // Flush cart session
        SessionCart::destroy();
        return redirect('/checking-out/'.$order->id);
    }

    /**
     * Display checkout page.
     *
     * @param int $order_id
     * @return Response
     */
    public function checkingOut($order_id)
    {
        $order          = Order::find($order_id);
        $user           = User::find($order->user_id);
        $fullname       = explode(' ', $user->name);
        $auth           = $this->authPaymob(); // login paymob servers
        if (property_exists($auth, 'detail')) {
            return redirect('tires');
        }
        $payment_key    = $this->getPaymentKeyPaymob( // get payment key
            $auth->token,
            $order->totalCost * 100,
            $order->paymob_order_id,
            // For billing data
            $user->email,
            $user->firstname,
            $user->lastname,
            $user->phone,
            $city->name
        );
        $token          = $payment_key->token;

        return view('frontend.checkout', compact('order', 'user', 'token'));
    }

    /**
     * Make payment on PayMob for API.
     *
     * @param Reuqest $request
     * @return Response
     */
    public function payAPI(Request $request)
    {
        $this->validate($request, [
            'card_number'     => 'required|numeric|digits:16',
            'card_holdername' => 'required|string|max:255',
            'card_expiry_mm'  => 'required|integer|max:12',
            'card_expiry_yy'  => 'required|integer',
            'card_cvn'        => 'required|integer|digits:3',
        ]);

        $user    = auth()->user();
        $order   = config('paymob.order.model', 'App\Order')::findOrFail($request->orderId);
        $payment = $this->makePayment( // make transaction on Paymob servers.
          $order->payment_key_token,
          $request->card_number,
          $request->card_holdername,
          $request->card_expiry_mm,
          $request->card_expiry_yy,
          $request->card_cvn,
          $order->paymob_order_id,
          $user->name,
          $user->email,
          $user->phone
        );

        # code...
    }

    /**
     * Transaction succeeded.
     *
     * @param  object  $order
     * @return void
     */
    protected function succeeded($order)
    {
        # code...
    }

    /**
     * Transaction voided.
     *
     * @param  object  $order
     * @return void
     */
    protected function voided($order)
    {
        # code...
    }

    /**
     * Transaction refunded.
     *
     * @param  object  $order
     * @return void
     */
    protected function refunded($order)
    {
        # code...
    }

    /**
     * Transaction failed.
     *
     * @param  object  $order
     * @return void
     */
    protected function failed($order)
    {
        # code...
    }

    /**
     * Processed callback from PayMob servers.
     *
     * @param  \Illumiante\Http\Request  $request
     * @return  Response
     */
    public function processedCallback(Request $request)
    {
        $orderId = $request['obj']['order']['id'];
        $order   = config('paymob.order.model', 'App\Order')::wherePaymobOrderId($orderId)->first();

        // Statuses.
        $isSuccess  = $request['obj']['success'];
        $isVoided   = $request['obj']['is_voided'];
        $isRefunded = $request['obj']['is_refunded'];

        if ($isSuccess && !$isVoided && !$isRefunded) { // transcation succeeded.
            $this->succeeded($order);
        } elseif ($isSuccess && $isVoided) { // transaction voided.
            $this->voided($order);
        } elseif ($isSuccess && $isRefunded) { // transaction refunded.
            $this->refunded($order);
        } elseif (!$isSuccess) { // transaction failed.
            $this->failed($order);
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * Display invoice page (PayMob response callback).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function invoice(Request $request)
    {
        # code...
    }

}
