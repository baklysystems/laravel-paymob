<?php

/**
 * IoC PayMob
 *
 * @author Mostafa El Bakly
 * @author Mohamed Abdul-Fattah
 * @license MIT
 */

namespace BaklySystems\PayMob;

class PayMob
{
    public function __construct()
    {
        //
    }

    /**
     * Send curl request to paymob servers.
     *
     * @param string url
     * @param array json
     * @return JSON
     */
    protected function curl($url, $json)
    {
        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array();
        $headers[] = 'Content-Type: application/json';

        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output);
    }

    /**
     * Request auth token from paymob servers.
     *
     * @return Array $auth
     */
    public function authCurlPaymob()
    {
        // Request body
        $json = [
          'username' => env('PAYMOB_USERNAME'),
          'password' => env('PAYMOB_PASSWORD')
      ];

        // Send curl
        $auth = self::curl(
        'https://accept.paymobsolutions.com/api/auth/tokens',
        $json
      );

        return $auth;
    }

    /**
     * Register order to paymob servers
     *
     * @param string $token
     * @param int $merchant_id
     * @param int $amount_cents
     * @param int $merchant_order_id
     * @return Array $order
     */
    public function makeOrderPaymob($token, $merchant_id, $amount_cents, $merchant_order_id)
    {
        // Request body
        $json = [
          'merchant_id' => $merchant_id,
          'amount_cents' => $amount_cents,
          'merchant_order_id' => $merchant_order_id,
          'currency' => 'EGP',
          'notify_user_with_email' => true
      ];

        // Send curl
        $order = self::curl(
        'https://accept.paymobsolutions.com/api/ecommerce/orders?token='.$token,
        $json
      );

        return $order;
    }

    /**
     * Get payment key to load iframe on paymob servers
     *
     * @param string $token
     * @param int $amount_cents
     * @param int $order_id
     * @param string $email
     * @param string $fname
     * @param string $lname
     * @param int $phone
     * @param string $city
     * @return string
     */
    public function getPaymentKeyPaymob(
          $token,
          $amount_cents,
          $order_id,
          $email,
          $fname,
          $lname,
          $phone,
          $city
      ) {
        // Request body
        $json = [
          'amount_cents' => $amount_cents,
          'expiration' => 36000,
          'order_id' => $order_id,
          "billing_data" => [
              "email" => $email,
              "first_name" => $fname,
              "last_name" => $lname,
              "phone_number" => $phone,
              "city" => $city,
              "country" => "EG",
              'street' => 'null',
              'building' => 'null',
              'floor' => 'null',
              'apartment' => 'null'
          ],
          'currency' => 'EGP',
          'card_integration_id' => env('PAYMOB_CARD_INTEGRATION_ID')
      ];

        // Send curl
        $payment_key = self::curl(
        'https://accept.paymobsolutions.com/api/acceptance/payment_keys?token='.$token,
        $json
      );

        return $payment_key;
    }

    /**
     * Make payment.
     *
     * @param string $token
     * @param int $card_number
     * @param string $card_holdername
     * @param int $card_expiry_mm
     * @param int $card_expiry_yy
     * @param int $card_cvn
     * @param int $order_id
     * @param string $name
     * @param string $email
     * @param string $phone
     * @return
     */
    public function makePayment(
    $token,
    $card_number,
    $card_holdername,
    $card_expiry_mm,
    $card_expiry_yy,
    $card_cvn,
    $order_id,
    $name,
    $email,
    $phone
    ) {
        $full_name = explode(' ', $name);

        // JSON body.
        $json = [
      'source' => [
        'identifier' => $card_number,
        'sourceholder_name' => $card_holdername,
        'subtype' => 'CARD',
        'expiry_month' => $card_expiry_mm,
        'expiry_year' => $card_expiry_yy,
        'cvn' => $card_cvn
       ],
      'billing' => [
        'first_name' => $full_name[0],
        'last_name' => $full_name[1],
        'email' => $email,
        'phone_number' => $phone,
       ],
      'payment_token' => $token
    ];

        // Send curl
        $payment = self::curl(
      'https://accept.paymobsolutions.com/api/acceptance/payments/pay',
      $json
    );

        return $payment;
    }

    /**
     * Capture authed order.
     *
     * @param string $token
     * @param int $transactionId
     * @param int amount
     * @return Response
     */
    public function capture($token, $transactionId, $amount)
    {
        // JSON body.
        $json = [
        'transaction_id' => $transactionId,
        'amount_cents' => $amount
    ];

        // Send curl.
        $res = self::curl(
        'https://accept.paymobsolutions.com/api/acceptance/capture?token='.$token,
        $json
    );

        return $res;
    }
}
