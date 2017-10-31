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
     * Send POST cURL request to paymob servers.
     *
     * @param  string  $url
     * @param  array  $json
     * @return array
     */
    protected function cURL($url, $json)
    {
        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array();
        $headers[] = 'Content-Type: application/json';

        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output);
    }

    /**
     * Send GET cURL request to paymob servers.
     *
     * @param  string  $url
     * @return array
     */
    protected function GETcURL($url)
    {
        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array();
        $headers[] = 'Content-Type: application/json';

        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
     * @return array
     */
    public function authPaymob()
    {
        // Request body
        $json = [
            'username' => config('paymob.username'),
            'password' => config('paymob.password')
        ];

        // Send curl
        $auth = $this->cURL(
            'https://accept.paymobsolutions.com/api/auth/tokens',
            $json
        );

        return $auth;
    }

    /**
     * Register order to paymob servers
     *
     * @param  string  $token
     * @param  int  $merchant_id
     * @param  int  $amount_cents
     * @param  int  $merchant_order_id
     * @return array
     */
    public function makeOrderPaymob($token, $merchant_id, $amount_cents, $merchant_order_id)
    {
        // Request body
        $json = [
            'merchant_id'            => $merchant_id,
            'amount_cents'           => $amount_cents,
            'merchant_order_id'      => $merchant_order_id,
            'currency'               => 'EGP',
            'notify_user_with_email' => true
        ];

        // Send curl
        $order = $this->cURL(
            'https://accept.paymobsolutions.com/api/ecommerce/orders?token='.$token,
            $json
        );

        return $order;
    }

    /**
     * Get payment key to load iframe on paymob servers
     *
     * @param  string  $token
     * @param  int  $amount_cents
     * @param  int  $order_id
     * @param  string  $email
     * @param  string  $fname
     * @param  string  $lname
     * @param  int  $phone
     * @param  string  $city
     * @param  string  $country
     * @return array
     */
    public function getPaymentKeyPaymob(
          $token,
          $amount_cents,
          $order_id,
          $email   = 'null',
          $fname   = 'null',
          $lname   = 'null',
          $phone   = 'null',
          $city    = 'null',
          $country = 'null'
      ) {
        // Request body
        $json = [
            'amount_cents' => $amount_cents,
            'expiration'   => 36000,
            'order_id'     => $order_id,
            "billing_data" => [
                "email"        => $email,
                "first_name"   => $fname,
                "last_name"    => $lname,
                "phone_number" => $phone,
                "city"         => $city,
                "country"      => $country,
                'street'       => 'null',
                'building'     => 'null',
                'floor'        => 'null',
                'apartment'    => 'null'
            ],
            'currency'            => 'EGP',
            'card_integration_id' => config('paymob.integration_id')
        ];

        // Send curl
        $payment_key = $this->cURL(
            'https://accept.paymobsolutions.com/api/acceptance/payment_keys?token='.$token,
            $json
        );

        return $payment_key;
    }

    /**
     * Make payment for API (moblie clients).
     *
     * @param  string  $token
     * @param  int  $card_number
     * @param  string  $card_holdername
     * @param  int  $card_expiry_mm
     * @param  int  $card_expiry_yy
     * @param  int  $card_cvn
     * @param  int  $order_id
     * @param  string  $firstname
     * @param  string  $lastname
     * @param  string  $email
     * @param  string  $phone
     * @return array
     */
    public function makePayment(
        $token,
        $card_number,
        $card_holdername,
        $card_expiry_mm,
        $card_expiry_yy,
        $card_cvn,
        $order_id,
        $firstname,
        $lastname,
        $email,
        $phone
    ) {
        // JSON body.
        $json = [
          'source' => [
            'identifier'        => $card_number,
            'sourceholder_name' => $card_holdername,
            'subtype'           => 'CARD',
            'expiry_month'      => $card_expiry_mm,
            'expiry_year'       => $card_expiry_yy,
            'cvn'               => $card_cvn
           ],
          'billing' => [
            'first_name'   => $firstname,
            'last_name'    => $lastname,
            'email'        => $email,
            'phone_number' => $phone,
           ],
          'payment_token' => $token
        ];

        // Send curl
        $payment = $this->cURL(
          'https://accept.paymobsolutions.com/api/acceptance/payments/pay',
          $json
        );

        return $payment;
    }

    /**
     * Capture authed order.
     *
     * @param  string  $token
     * @param  int  $transactionId
     * @param  int  amount
     * @return array
     */
    public function capture($token, $transactionId, $amount)
    {
        // JSON body.
        $json = [
            'transaction_id' => $transactionId,
            'amount_cents'   => $amount
        ];

        // Send curl.
        $res = $this->cURL(
            'https://accept.paymobsolutions.com/api/acceptance/capture?token='.$token,
            $json
        );

        return $res;
    }

    /**
     * Get PayMob all orders.
     *
     * @param  string  $authToken
     * @param  string  $page
     * @return Response
     */
    public function getOrders($authToken, $page = 1)
    {
        $orders = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/ecommerce/orders?page={$page}&token={$authToken}"
        );

        return $orders;
    }

    /**
     * Get PayMob order.
     *
     * @param  string  $authToken
     * @param  int  $orderId
     * @return Response
     */
    public function getOrder($authToken, $orderId)
    {
        $order = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/ecommerce/orders/{$orderId}?token={$authToken}"
        );

        return $order;
    }

    /**
     * Get PayMob all transactions.
     *
     * @param  string  $authToken
     * @param  string  $page
     * @return Response
     */
    public function getTransactions($authToken, $page = 1)
    {
        $transactions = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/acceptance/transactions?page={$page}&token={$authToken}"
        );

        return $transactions;
    }

    /**
     * Get PayMob transaction.
     *
     * @param  string  $authToken
     * @param  int  $transactionId
     * @return Response
     */
    public function getTransaction($authToken, $transactionId)
    {
        $transaction = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/acceptance/transactions/{$transactionId}?token={$authToken}"
        );

        return $transaction;
    }

    /**
     * Get sample responses for PayMob requests.
     *
     * @param  string  $method
     * @return array
     */
    public function sample($method = null)
    {
        // Auth request response.
        $auth = [
            'token' => "ZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6VXhNaUo5LmV5SndhR0Z6YUNJNkltSmpjbmx3ZEY5emFHRXlOVFlrSkRKaUpERXlKRTl1VkRGb1YxSnRVRVF6UkVWdGR6UmFZVGhQTVhVM1QxUlNhMlZYYmpoalQwUXViM0ozVDJFME1WRnlMemxuWjNkNFVFaFhJaXdpWTJ4aGMzTWlPaUpOWlhKamFHRnVkQ0lzSW1WNGNDSTZNVFV3T1RNNE5UYzVOQ3dpY0hKdlptbHNaVjl3YXlJNk1UZ3dmUS5UdG16ekFPaFpBSWhoSXk2WnBVM2dVdmFuYnRoMlQ2d1h6Qy1zaHlwVVlXMndHUDlVem9UZ1I4T3lmVWFlYU84OElYOVI5azlTMWd5VS1OaVhjeVpUUQ==",
            'profile' => [
                'id' => 180,
                'user' => [
                    'id' => 197,
                    'username' => "csmohamed",
                    'first_name' => "",
                    'last_name' => "",
                    'date_joined' => "2017-07-20T14=>50=>26",
                    'email' => "csmohamed8@gmail.com",
                    'is_active' => true,
                    'is_staff' => false,
                    'is_superuser' => false,
                    'last_login' => null,
                    'groups' => [ ],
                    'user_permissions' => [ ]
                ],
                'created_at' => "2017-07-20T14:50:26.417338",
                'active' => true,
                'profile_type' => "Merchant",
                'phones' => [ ],
                'company_emails' => [
                    "elbakly@gmail.com"
                ],
                'company_name' => "BaklySystems",
                'state' => "",
                'country' => "",
                'city' => "",
                'postal_code' => "",
                'street' => "",
                'email_notification' => false,
                'order_retrieval_endpoint' => null,
                'delivery_update_endpoint' => null,
                'failed_attempts' => 0,
                'awb_banner' => null,
                'email_banner' => null
            ]
        ];

        // Make order request.
        $makeOrder = [
            "id" => 77614,
            "created_at" => "2017-10-30T20:20:43.455315",
            "delivery_needed" => false,
            "merchant" => [
                "id" => 180,
                "created_at" => "2017-07-20T14:50:26.417338",
                "phones" => [],
                "company_emails" => [
                    "elbakly@gmail.com"
                ],
                "company_name" => "BaklySystems",
                "state" => "",
                "country" => "",
                "city" => "",
                "postal_code" => "",
                "street" => ""
            ],
            "collector" => null,
            "amount_cents" => 100,
            "shipping_data" => null,
            "currency" => "EGP",
            "is_payment_locked" => false,
            "merchant_order_id" => "60019",
            "wallet_notification" => null,
            "paid_amount_cents" => 0,
            "notify_user_with_email" => false,
            "items" => [],
            "order_url" => "https://accept.paymobsolutions.com/invoice?token=ZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6VXhNaUo5LmV5SmpiR0Z6Y3lJNklrOXlaR1Z5SWl3aWIzSmtaWEpmY0dzaU9qYzNOakUwZlEuMHQtc3BNeWkwSG1GTkdJakVYczJacHVkeXFyTDg1T2pzNjV2b0tSWGNNY29XcUZZRTZMdkpONnB3WDlqYnpMbDhyRE0tRFdBVWtQbTZqN1RoeGFzX1E=",
            "commission_fees" => 0,
            "delivery_fees" => 0
        ];

        // Get payment key request.
        $paymentKey = [
            "token" => "ZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6VXhNaUo5LmV5SnZjbVJsY2w5cFpDSTZOemMyTXpNc0ltVjRjQ0k2TVRVd09UUXlOVFUzTml3aVlXMXZkVzUwWDJObGJuUnpJam8wTURBd01Dd2lkWE5sY2w5cFpDSTZNVGszTENKamRYSnlaVzVqZVNJNklrVkhVQ0lzSW1OaGNtUmZhVzUwWldkeVlYUnBiMjVmYVdRaU9qTXlPU3dpWW1sc2JHbHVaMTlrWVhSaElqcDdJbVpwY25OMFgyNWhiV1VpT2lKdWRXeHNJaXdpYkdGemRGOXVZVzFsSWpvaWJuVnNiQ0lzSW5OMGNtVmxkQ0k2SW01MWJHd2lMQ0ppZFdsc1pHbHVaeUk2SW01MWJHd2lMQ0ptYkc5dmNpSTZJbTUxYkd3aUxDSmhjR0Z5ZEcxbGJuUWlPaUp1ZFd4c0lpd2lZMmwwZVNJNkltNTFiR3dpTENKemRHRjBaU0k2SWs1Qklpd2lZMjkxYm5SeWVTSTZJbTUxYkd3aUxDSmxiV0ZwYkNJNkltNTFiR3dpTENKd2FHOXVaVjl1ZFcxaVpYSWlPaUp1ZFd4c0lpd2ljRzl6ZEdGc1gyTnZaR1VpT2lKT1FTSjlmUS5KSENobkFXM0hWcjVXZlo3eTFsdmJ4Z3dzOEUxTkhJZ190eEJhejhDX2xhaDRla192SWJfOTY4VlZWMG5HVXBhTWlERmxYMnZ3NkpfbFhNdUZ0SHRjZw=="
        ];

        // Processed callback response.
        $processed = [
            'obj' =>
              array (
                'id' => 36303,
                'pending' => false,
                'amount_cents' => 33300,
                'success' => true,
                'is_auth' => true,
                'is_capture' => false,
                'is_standalone_payment' => true,
                'is_voided' => false,
                'is_refunded' => false,
                'is_3d_secure' => false,
                'integration_id' => 329,
                'profile_id' => 180,
                'has_parent_transaction' => false,
                'order' =>
                array (
                  'id' => 68010,
                  'created_at' => '2017-10-09T13:13:50.234703',
                  'delivery_needed' => false,
                  'merchant' =>
                  array (
                    'id' => 180,
                    'created_at' => '2017-07-20T14:50:26.417338',
                    'phones' =>
                    array (
                    ),
                    'company_emails' =>
                    array (
                      0 => 'elbakly@gmail.com',
                    ),
                    'company_name' => 'BaklySystems',
                    'state' => NULL,
                    'country' => NULL,
                    'city' => NULL,
                    'postal_code' => NULL,
                    'street' => NULL,
                  ),
                  'collector' => NULL,
                  'amount_cents' => 33300,
                  'shipping_data' =>
                  array (
                    'id' => 46463,
                    'first_name' => 'Mohamed',
                    'last_name' => 'Abdul-Fattah',
                    'street' => 'null',
                    'building' => 'null',
                    'floor' => 'null',
                    'apartment' => 'null',
                    'city' => 'El Gouna',
                    'state' => 'NA',
                    'country' => 'EG',
                    'email' => 'csmohamed8@gmail.com',
                    'phone_number' => '0123456789',
                    'postal_code' => 'NA',
                    'extra_description' => NULL,
                    'shipping_method' => 'UNK',
                    'order_id' => 68010,
                    'order' => 68010,
                  ),
                  'currency' => 'EGP',
                  'is_payment_locked' => false,
                  'merchant_order_id' => '55981',
                  'wallet_notification' => NULL,
                  'paid_amount_cents' => 33300,
                  'notify_user_with_email' => true,
                  'items' =>
                  array (
                  ),
                  'order_url' => 'https://accept.paymobsolutions.com/invoice?token=ZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6VXhNaUo5LmV5SnZjbVJsY2w5d2F5STZOamd3TVRBc0ltTnNZWE56SWpvaVQzSmtaWElpZlEubEU5eWsxWGkzaFRwNU5GTk1mNlMwRzV1M3RtczRyQmszREdmZEdpVWs5ZVY0VFdMTzVoV0ZrMV85eGhxY0VTYXpabElaWWx6TDQ0SWJlRE4zY0VlcVE=',
                  'commission_fees' => 0,
                  'delivery_fees' => 0,
                ),
                'created_at' => '2017-10-09T13:14:01.027560',
                'transaction_processed_callback_responses' =>
                array (
                ),
                'currency' => 'EGP',
                'source_data' =>
                array (
                  'type' => 'card',
                  'pan' => '8769',
                  'sub_type' => 'Visa',
                ),
                'is_void' => false,
                'is_refund' => false,
                'data' =>
                array (
                  'currency' => 'EGP',
                  'secure_hash' => '842374DE4624422275F8071791C89A6A3705CDC71BB4B2BA6764DF67FE2ED7DB',
                  'authorize_id' => '418254',
                  'acq_response_code' => '00',
                  'command' => 'pay',
                  'klass' => 'VPCPayment',
                  'avs_result_code' => 'Unsupported',
                  'receipt_no' => '728222418254',
                  'avs_acq_response_code' => 'Unsupported',
                  'merchant_txn_ref' => '329_76180a58961553a4bb742ff7c6e87a7a',
                  'gateway_integration_pk' => 329,
                  'message' => 'Approved',
                  'txn_response_code' => '0',
                  'card_type' => 'VC',
                  'merchant' => 'TEST290510EGP',
                  'order_info' => 'csmohamed8@gmail.com',
                  'created_at' => '2017-10-09T11:14:03.369050',
                  'batch_no' => '20171009',
                  'card_num' => 'xxxxxxxxxxxx8769',
                  'transaction_no' => '2000009194',
                  'amount' => '33300',
                ),
                'is_hidden' => false,
                'payment_key_claims' =>
                array (
                  'amount_cents' => 33300,
                  'exp' => 1507583631,
                  'order_id' => 68010,
                  'card_integration_id' => 329,
                  'billing_data' =>
                  array (
                    'first_name' => 'Mohamed',
                    'country' => 'EG',
                    'city' => 'El Gouna',
                    'floor' => 'null',
                    'email' => 'csmohamed8@gmail.com',
                    'street' => 'null',
                    'last_name' => 'Abdul-Fattah',
                    'building' => 'null',
                    'postal_code' => 'NA',
                    'state' => 'NA',
                    'apartment' => 'null',
                    'phone_number' => '0123456789',
                  ),
                  'currency' => 'EGP',
                  'user_id' => 197,
                ),
                'error_occured' => false,
                'is_live' => false,
                'other_endpoint_reference' => NULL,
                'refunded_amount_cents' => 0,
                'is_captured' => false,
                'captured_amount' => 0,
                'owner' => 197,
                'parent_transaction' => NULL,
              ),
              'type' => 'TRANSACTION',
              'hmac' => '45ba18dfe268d9504a3237aba78c0fbf10a953f9c7059a83527ce0fc3fc605c831eaf3698dd34f6550f66db8cb9806f492e273c7fa65018718e0bb100024d888',
        ];

        // Approved transaction response callback.
        $response = [
            "source_data_type" => "card",
            "created_at" => "2017-09-11T13:25:49.224344",
            "success" => "true",
            "is_standalone_payment" => "true",
            "error_occured" => "false",
            "is_refund" => "false",
            "currency" => "EGP",
            "profile_id" => "180",
            "source_data_sub_type" => "Visa",
            "refunded_amount_cents" => "0",
            "id" => "29187","is_void"=>"false",
            "is_voided" => "false",
            "captured_amount" => "0",
            "owner" => "197",
            "is_auth" => "false",
            "is_capture" => "false",
            "source_data_pan" => "8769",
            "pending" => "false",
            "integration_id" => "267",
            "hmac" => "653c9ce5ab6d36b84f6f43b0f017ff8b69fb696f2d0ac62b1fbf015bd4326fa7ab9183809e2bbb85b04c6d15aa390e19f2a75902908c6f298c0a42ca49b41101",
            "amount_cents" => "20000",
            "data_message" => "Approved",
            "is_refunded" => "false",
            "is_3d_secure" => "false",
            "order" => "56081",
            "has_parent_transaction" => "false"
        ];

        // Auth type response callback.
        $authCallback = [
            "currency" => "EGP",
            "is_refund" => "false",
            "is_refunded" => "false",
            "pending" => "false",
            "error_occured" => "false",
            "is_auth" => "true",
            "source_data_pan" => "8769",
            "is_standalone_payment" => "true",
            "source_data_sub_type" => "Visa",
            "source_data_type" => "card",
            "is_3d_secure" => "false",
            "owner" => "197",
            "created_at" => "2017-09-18T15:39:21.786896",
            "success" => "true",
            "profile_id" => "180",
            "captured_amount" => "0",
            "data_message" => "Approved",
            "amount_cents" => "240000",
            "is_void" => "false",
            "refunded_amount_cents" => "0",
            "hmac" => "e5c8a52010809495a5a25b9005055d92b99f44d9e8041966ff0dfc1e80bd2f23337bf94d45297eafd1093ed26adafabdbcba47f773695552998f5b980b02b2ff",
            "has_parent_transaction" => "false",
            "is_capture" => "false",
            "order" => "59296",
            "id" => "31157",
            "integration_id" => "329",
            "is_voided" => "false"
        ];

        // Capture callback response.
        $capture = [
            "id" => 31393,
            "pending" => false,
            "amount_cents" => 130,
            "success" => true,
            "is_auth" => false,
            "is_capture" => true,
            "is_standalone_payment" => false,
            "is_voided" => false,
            "is_refunded" => false,
            "is_3d_secure" => false,
            "integration_id" => 329,
            "profile_id" => 180,
            "has_parent_transaction" => true,
            "order" => [
                "id" => 59619,
                "created_at" => "2017-09-19T02:21:03.183787",
                "delivery_needed" => false,
                "merchant" => [
                    "id" => 180,
                    "created_at" => "2017-07-20T14:50:26.417338",
                    "phones" => [],
                    "company_emails" => [
                        "elbakly@gmail.com"
                    ],
                    "company_name" => "BaklySystems",
                    "state" => "",
                    "country" => "",
                    "city" => "",
                    "postal_code" => "",
                    "street" => ""
                ],
                "collector" => null,
                "amount_cents" => 13000,
                "shipping_data" => [
                    "id" => 40001,
                    "first_name" => "Mohamed",
                    "last_name" => "Abdul-Fattah",
                    "street" => "null",
                    "building" => "null",
                    "floor" => "null",
                    "apartment" => "null",
                    "city" => "Cairo",
                    "state" => "NA",
                    "country" => "EG",
                    "email" => "csmohamed8@gmail.com",
                    "phone_number" => "0123456789",
                    "postal_code" => "NA",
                    "extra_description" => "",
                    "shipping_method" => "UNK",
                    "order_id" => 59619,
                    "order" => 59619
                ],
                "currency" => "EGP",
                "is_payment_locked" => false,
                "merchant_order_id" => "42145",
                "wallet_notification" => null,
                "paid_amount_cents" => 13000,
                "notify_user_with_email" => true,
                "items" => [],
                "order_url" => "https://accept.paymobsolutions.com/invoice?token=ZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6VXhNaUo5LmV5SnZjbVJsY2w5d2F5STZOVGsyTVRrc0ltTnNZWE56SWpvaVQzSmtaWElpZlEuSHpVR1JUX2lyZ1kxQ0J0LW9sYjJTUE12eU9xNlNjR3hocnNQMWRNTXpOdGhqaHVVaVRXdnVkWkx3Z3JJVlNDakh1NWxITi1hMk1hU3RmU3hYNzEzZEE=",
                "commission_fees" => 0,
                "delivery_fees" => 0
            ],
            "created_at" => "2017-09-19T02=>42:47.874768",
            "transaction_processed_callback_responses" => [],
            "currency" => "EGP",
            "source_data" => [
                "pan" => "8769",
                "sub_type" => "Visa",
                "type" => "card"
            ],
            "is_void" => false,
            "is_refund" => false,
            "data" => [
                "acq_response_code" => null,
                "klass" => "VPCCapture",
                "created_at" => "2017-09-19T00:42:49.288708",
                "receipt_no" => "726210610984",
                "batch_no" => "20170919",
                "version" => "1'",
                "txn_response_code" => "0",
                "currency" => "EGP",
                "command" => "capture",
                "amount" => "130",
                "gateway_integration_pk" => 329,
                "refunded_amount" => "0",
                "card" => "VC",
                "merchant" => "TEST290510EGP",
                "authorised_amount" => "13000",
                "transaction_no" => "2000008138",
                "captured_amount" => "130",
                "locale" => "en_US",
                "shop_transaction_no" => "2000008137",
                "message" => "Approved",
                "merchant_txn_ref" => "329_0bd69b355d5a5e33bcf86008ef5c7d05"
            ],
            "is_hidden" => false,
            "payment_key_claims" => null,
            "error_occured" => false,
            "is_live" => false,
            "other_endpoint_reference" => null,
            "refunded_amount_cents" => 0,
            "is_captured" => false,
            "captured_amount" => 0,
            "owner" => 197,
            "parent_transaction" => 31392
        ];

        // Failed transaction response.
        $error = [
            "source_data_type" => "card",
            "created_at" => "2017-09-11T13:14:54.346980",
            "success" => "false",
            "is_standalone_payment" => "true",
            "error_occured" => "false",
            "is_refund" => "false",
            "currency" => "EGP",
            "profile_id" => "180",
            "source_data_sub_type" => "MasterCard",
            "refunded_amount_cents" => "0",
            "id" => "29176",
            "is_void" => "false",
            "is_voided" => "false",
            "captured_amount" => "0",
            "owner" => "197",
            "is_auth" => "false",
            "is_capture" => "false",
            "source_data_pan" => "8769",
            "pending" => "false",
            "integration_id" => "267",
            "hmac" => "286fac1463716c3d2fec9c1a9facf5372924ec53d14633bea3361e8559cf1bb9e73beac2e5f0b6403e99ce78aa673478dcaafd89c7966fc444005c93ec545ab1",
            "amount_cents" => "20000",
            "data_message" => "I5154-09112114: Invalid Card Number",
            "is_refunded" => "false",
            "is_3d_secure" => "false",
            "order" => "56081",
            "has_parent_transaction" => "false"
        ];

        if ($method === 'authPaymob') {
            return $auth;
        } elseif ($method === 'makeOrderPaymob') {
            return $makeOrder;
        } elseif ($method === 'getPaymentKeyPaymob') {
            return $paymentKey;
        } elseif ($method === 'processedCallback') {
            return $processed;
        } elseif ($method === 'responseCallback') {
            return $response;
        } elseif ($method === 'authCallback') {
            return $authCallback;
        } elseif ($method === 'captureCallback') {
            return $capture;
        } elseif ($method === 'errorCallback') {
            return $error;
        } else {
            $methods = [
                'availablie samples' => [
                    'authPaymob',
                    'makeOrderPaymob',
                    'getPaymentKeyPaymob',
                    'processedCallback',
                    'responseCallback',
                    'authCallback',
                    'captureCallback',
                    'errorCallback'
                ]
            ];

            return $methods;
        }
    }
}
