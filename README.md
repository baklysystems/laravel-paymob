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
## Steps to make a transaction on PayMob servers

1. API Authentication Request
2. Order Registration Request
3. Payment Key Generation Request
4. Prepare Client Code to Perform Payment Request (Webclients and mobile apps)
5. Merchant Notification Endpoint
6. Transaction Response Endpoint

#### 1. API Authentication Request (server side)

In this step you are required to perform a post request to Accept's authentication API to obtain authentication token

Use PayMob Facade to make the auth.

```php
$auth = PayMob::authPaymob();
// Run this method to get a sample response of auth request.
PayMob::sample('authPaymob');
```

This method gets the credentials from `config/paymob.php` file, so fill in `username` and `password` first to make this auth request.

#### 2. Order Registration Request (server side)

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

#### 3. Payment Key Generation Request (server side)

At this step you will obtain a `payment_key` token. This key will be used to authenticate your payment request.

```php
$paymentKey = PayMob::getPaymentKeyPaymob(
    $auth->token, // from step 1.
    $order->totalCost * 100, // total amount by cents/piasters.
    $order->paymob_order_id, // paymob order id from step 2.
    // For billing data
    $user->email,
    $user->firstname,
    $user->lastname,
    $user->phone,
    $city->name
);
// Run this method to get a sample response of payment key request.
PayMob::sample('getPaymentKeyPaymob');
```

#### 4. Prepare Client Code to Perform Payment Request (Webclients and mobile apps) (client side)

Now that you have obtained payment key, you need to prepare your checkout experience (i.e. client-side code).

##### Iframe for websites/webapps

```html
<iframe src="https://accept.paymobsolutions.com/api/acceptance/iframes/{{config('paymob.iframe_id')}}?payment_token={{$payemntKey->token}}"></iframe>
```
##### Mobile clients
