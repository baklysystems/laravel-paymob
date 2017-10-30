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
     * @param  string  $url
     * @param  array  $json
     * @return array
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
        $auth = $this->curl(
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
            'merchant_id' => $merchant_id,
            'amount_cents' => $amount_cents,
            'merchant_order_id' => $merchant_order_id,
            'currency' => 'EGP',
            'notify_user_with_email' => true
        ];

        // Send curl
        $order = $this->curl(
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
     * @return array
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
            'card_integration_id' => config('paymob.integration_id')
        ];

        // Send curl
        $payment_key = $this->curl(
            'https://accept.paymobsolutions.com/api/acceptance/payment_keys?token='.$token,
            $json
        );

        return $payment_key;
    }

    /**
     * Make payment.
     *
     * @param  string  $token
     * @param  int  $card_number
     * @param  string  $card_holdername
     * @param  int  $card_expiry_mm
     * @param  int  $card_expiry_yy
     * @param  int  $card_cvn
     * @param  int  $order_id
     * @param  string  $name
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
        $payment = $this->curl(
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
            'amount_cents' => $amount
        ];

        // Send curl.
        $res = $this->curl(
            'https://accept.paymobsolutions.com/api/acceptance/capture?token='.$token,
            $json
        );

        return $res;
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
                'created_at' => "2017-07-20T14=>50=>26.417338",
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

        if ($method === 'authPaymob') {
            return $auth;
        }
    }
}
