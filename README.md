# Laravel PayMob

A Laravel online payment gateway.

## Installation

Require via composer

```bash
$ composer require baklysystems/laravel-paymob
```

In `config/app.php` file

```php
'providers' => [
    ...
    BaklySystems\PayMob\PayMobServiceProvider::class,
    ...
];

'aliases' => [
    ...
    'PayMob' => BaklySystems\PayMob\Facades\PayMob::class,
    ...
];
```

First of all, make an account on [WeAccept portal](https://www.weaccept.co/portal/login), then fill in the credentials in `config/paymob.php` file.
Make sure to make an iframe in your dashboard and get the integration id for payment requests.


## Steps to make a transaction on PayMob servers

1. API Authentication Request
2. Order Registration Request
3. Payment Key Generation Request
4. Prepare Client Code to Perform Payment Request (Webclients and mobile apps)
5. Merchant Notification Endpoint
6. Transaction Response Endpoint

You can refer to [PayMob online guide](https://accept.paymobsolutions.com/docs/guide/online-guide/) for more information.

### 1\. API Authentication Request (server side)

In this step you are required to perform a post request to PayMob's authentication API to obtain authentication token

Use PayMob Facade to make requests.

```php
$auth = PayMob::authPaymob();
// Run this method to get a sample response of auth request.
PayMob::sample('authPaymob');
```

This method gets the credentials from `config/paymob.php` file, so fill in `username` and `password` first to make this auth request.

### 2\. Order Registration Request (server side)

At this step you will register an order on Paymob Accept so that you can pay for it later using a transaction.

```php
$paymobOrder = PayMob::makeOrderPaymob(
    $auth->token, // this is token from step 1.
    $auth->profile->id, // this is the merchant id from step 1.
    $order->totalCost * 100, // total amount by cents/piasters.
    $order->id // your (merchant) order id.
);
// Run this method to get a sample response of make order request.
PayMob::sample('makeOrderPaymob');
```

Store the returned paymob order id in your DB to make transactions with using this id in future.

### 3\. Payment Key Generation Request (server side)

At this step you will obtain a `payment_key` token. This key will be used to authenticate your payment request.

```php
$paymentKey = PayMob::getPaymentKeyPaymob(
    $auth->token, // from step 1.
    $order->totalCost * 100, // total amount by cents/piasters.
    $order->paymob_order_id, // paymob order id from step 2.
    // For billing data
    $user->email, // optional
    $user->firstname, // optional
    $user->lastname, // optional
    $user->phone, // optional
    $city->name, // optional
    $country->name // optional
);
// Run this method to get a sample response of payment key request.
PayMob::sample('getPaymentKeyPaymob');
```

### 4\. Prepare Client Code to Perform Payment Request (Webclients and mobile apps) (client side)

Now that you have obtained payment key, you need to prepare your checkout experience (i.e. client-side code).

#### Iframe for websites/webapps

PayMob recommended iframe

```html
<form id="paymob_checkout">
    <label for="">Card number</label>
      <input type="text" value="4987654321098769" paymob_field="card_number">
      <br>
      <label for="">Card holdername</label>
      <input type="text" value="Test Account" paymob_field="card_holdername">
      <br>
      <label for="">Card month</label>
      <input type="text" value="05" paymob_field="card_expiry_mm">
      <br>
      <label for="">Card year</label>
      <input type="text" value="21" paymob_field="card_expiry_yy">
      <br>
      <label for="">Card cvn</label>
      <input type="text" value="123" paymob_field="card_cvn">
      <input type="hidden" value="CARD" paymob_field="subtype">
      <input type="checkbox" value="tokenize" name="save card"> <label for="save card">save card</label>

      <input type="submit" value="Pay">
      <br>

</form>
```

```html
<iframe src="https://accept.paymobsolutions.com/api/acceptance/iframes/{{config('paymob.iframe_id')}}?payment_token={{$payemntKey->token}}"></iframe>
```

#### Mobile clients

```php
$payment = PayMob::makePayment(
    $paymentKey->token, // payemnt key token from step 3.
    $request->card_number,
    $request->card_holdername,
    $request->card_expiry_mm,
    $request->card_expiry_yy,
    $request->card_cvn,
    $order->paymob_order_id, // PayMob order id from step 2.
    $user->firstname,
    $user->lastname,
    $user->email,
    $user->phone
);
// Run this method to get a sample response of make payment for API request.
// processedCallback is for the post response to your processed callback route from PayMob.
PayMob::sample('processedCallback');
// responseCallback is for the Get response to your response callback route from PayMob.
PayMob::sample('responseCallback');
```

You can use some [test cards](https://accept.paymobsolutions.com/docs/guide/online-guide/#test-cards) to make a test payment.
