<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BaklySystems\PayMob\Facades\PayMob;

class PayMobController extends Controller
{

    /**
     * Display checkout page.
     *
     * @param  int  $orderId
     * @return Response
     */
    public function checkingOut($integration_id, $orderId)
    {
        $order       = config('paymob.order.model', 'App\Order')::find($orderId);
        # code... get order user.
        $auth        = PayMob::authPaymob(); // login PayMob servers
        if (property_exists($auth, 'detail')) { // login to PayMob attempt failed.
            # code... redirect to previous page with a message.
        }
        $paymobOrder = PayMob::makeOrderPaymob( // make order on PayMob
            $auth->token,
            $auth->profile->id,
            $order->totalCost * 100,
            $order->id
        );
        // Duplicate order id
        // PayMob saves your order id as a unique id as well as their id as a primary key, thus your order id must not
        // duplicate in their database. 
        if (isset($paymobOrder->message)) {
            if ($paymobOrder->message == 'duplicate') {
                # code... your order id is duplicate on PayMob database.
            }
        }
        $order->update(['paymob_order_id' => $paymobOrder->id]); // save paymob order id for later usage.
        $payment_key = PayMob::getPaymentKeyPaymob( // get payment key
            $integration_id,
            $auth->token,
            $order->totalCost * 100,
            $paymobOrder->id,
            // For billing data
            $user->email, // optional
            $user->firstname, // optional
            $user->lastname, // optional
            $user->phone, // optional
            $city->name, // optional
            $country->name // optional
        );

        # code... load view with iframe.
    }

    /**
     * Make payment on PayMob for API (mobile clients).
     * For PCI DSS Complaint Clients Only.
     *
     * @param  \Illuminate\Http\Reuqest  $request
     * @return Response
     */
    public function payAPI(Request $request)
    {
        $this->validate($request, [
            'orderId'         => 'required|integer',
            'card_number'     => 'required|numeric|digits:16',
            'card_holdername' => 'required|string|max:255',
            'card_expiry_mm'  => 'required|integer|max:12',
            'card_expiry_yy'  => 'required|integer',
            'card_cvn'        => 'required|integer|digits:3',
        ]);

        $user    = auth()->user();
        $order   = config('paymob.order.model', 'App\Order')::findOrFail($request->orderId);
        $payment = PayMob::makePayment( // make transaction on Paymob servers.
          $payment_key_token,
          $request->card_number,
          $request->card_holdername,
          $request->card_expiry_mm,
          $request->card_expiry_yy,
          $request->card_cvn,
          $order->paymob_order_id,
          $user->firstname,
          $user->lastname,
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
     * Save the route for this method in PayMob dashboard >> processed callback route.
     *
     * @param  \Illumiante\Http\Request  $request
     * @return  Response
     */
    public function processedCallback(Request $request)
    {
        $orderId = $request['obj']['order']['id'];
        $order   = config('paymob.order.model', 'App\Order')::wherePaymobOrderId($orderId)->first();

        // Statuses.
        $isSuccess  = filter_var($request['success'], FILTER_VALIDATE_BOOLEAN);
        $isVoided  = filter_var($request['is_voided'], FILTER_VALIDATE_BOOLEAN);
        $isRefunded  = filter_var($request['is_refunded'], FILTER_VALIDATE_BOOLEAN);

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
     * Save the route for this method to PayMob dashboard >> response callback route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function invoice(Request $request)
    {
        # code...
    }

}
