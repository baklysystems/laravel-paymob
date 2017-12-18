# Laravel PayMob

A Laravel online payment gateway.

## Table of Contents

1. [Installation](#installation)
2. [Steps to make a transaction on PayMob servers](#steps-to-make-a-transaction-on-paymob-servers)
    1. [API Authentication Request (server side)](#1-api-authentication-request-server-side)
    2. [Order Registration Request (server side)](#2-order-registration-request-server-side)
    3. [Payment Key Generation Request (server side)](#3-payment-key-generation-request-server-side)
    4. [Prepare Client Code to Perform Payment Request (Webclients and mobile apps) (client side)](#4-prepare-client-code-to-perform-payment-request-webclients-and-mobile-apps-client-side)
        1. [Iframe for websites/webapps](#iframe-for-websiteswebapps)
        2. [Mobile clients](#mobile-clients)

3. [PayMobController](#paymobcontroller)
4. [PayMob Postman Collection](#paymob-postman-collection)
5. [Other PayMob Methods](#other-paymob-methods)
6. [TODO](#todo)
7. [License](#license)

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

First of all, make an account on [WeAccept portal](https://www.weaccept.co/portal/login), run this command to generate the PayMob configuration file
```bash
$ php artisan vendor:publish    
```
Then fill in the credentials in `config/paymob.php` file. Make sure to make an iframe in your dashboard and get the integration id for payment requests.

Fill in the processed callback and response callback routes in integration details with the routes for `processedCallback` and `invoice` methods in `PayMobController`

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
<iframe src="https://accept.paymobsolutions.com/api/acceptance/iframes/{{config('paymob.iframe_id')}}?payment_token={{$paymentKey->token}}"></iframe>
```

#### Mobile clients

In case of mobile apps, you will need to import Accept native IOS or Android SDK to proceed with the payment and/or save the card details.

Please request the needed SDK by emailing support@weaccept.co
For more information visit [PayMob mobile guid](https://accept.paymobsolutions.com/docs/guide/online-guide/#step-4-prepare-your-client-code-client-side)

```php
$payment = PayMob::makePayment(
    $paymentKey->token, // payment key token from step 3.
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

You can run `PayMob::sample()` to see available samples.

## PayMobController

We have 4 methods in `PayMobController`.

First use `checkingOut` method to display the payment form page with the iframe. Or simply make payment using `payAPI` method for mobile clients.

Then, we have the `processedCallback` method to catch the `POST` callback response from PayMob servers, and `invoice` method to catch the `GET` callback response and display your invoice page.

Replace all `#code ...` with your logic.

Don't forget to make routes for these methods, and to save the `processedCallback` and `invoice` routes in the integration details in PayMob dashboard.

## PayMob Postman Collection

There is a [Postman collection](PayMob.postman_collection.json) for PayMob requests.

## Other PayMob Methods

There are some `GET` methods to get your data from PayMob.

### 1\. Get All Orders

```php
PayMob::getOrders(
    $auth->token, // token from step 1.
    $page // optional for pagination, by default set to 1
);
```

### 2\. Get a Specific Order

```php
PayMob::getOrder(
    $auth->token, // token from step 1.
    $order->paymob_order_id // PayMob order id from step 2.
);
```

### 3\. Get All Transactions

```php
PayMob::getTransactions(
    $auth->token, // token from step 1.
    $page // optional for pagination, by default set to 1
);
```

### 4\. Get a Specific Transaction

```php
PayMob::getTransaction(
    $auth->token, // token from step 1.
    $transactionId // PayMob transaction id from step 4.
);
```

### 5. Capture For Auth Transactions

If your transactions is `auth` type (not `standalone`), then you have to capture your payment through `capture` method.

```php
PayMob::capture(
    $auth->token, // token from step 1.
    $transactionId, // the returned id from step 4.
    $totalCost * 100 // total price/cost in cents/piasters.
);
```

## TODO

1. Invoice page.
2. Sample transaction cycle.
3. Get all orders/transactions page.
4. Refund from backend.
5. Iframe with JS validations.
6. Top level redirect request for 3D secure.

## License

Laravel PayMob is a free software distributed under the terms of the MIT license.
