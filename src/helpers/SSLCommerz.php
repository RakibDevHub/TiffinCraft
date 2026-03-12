<?php

class SSLCommerz
{
    private $storeId;
    private $storePassword;
    private $sandbox;
    
    private $error;

    public function __construct()
    {
        $this->storeId = SSLCOMMERZ_STORE_ID ?? 'tiffi6899ad31bafdc';
        $this->storePassword = SSLCOMMERZ_STORE_PASSWORD ?? 'tiffi6899ad31bafdc@ssl';
        $this->sandbox = !SSLCOMMERZ_LIVE;
    }

    public function makePayment($postData)
    {
        $api_url = $this->sandbox
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';

        // Required credentials
        $postData['cus_city'] = '';
        $postData['cus_state'] = '';
        $postData['cus_postcode'] = '';
        $postData['cus_country'] = '';
        $postData['store_id'] = $this->storeId;
        $postData['store_passwd'] = $this->storePassword;
        $postData['shipping_method'] = 'NO';
        $postData['product_profile'] = $postData['product_profile'] ?? 'non-physical-goods';

        // Subscription parameters
        if (isset($postData['value_a']) && $postData['value_a'] === 'SUBSCRIPTION') {
            $postData['subscription'] = '1';
            $postData['subscription_type'] = 'interval';
            $postData['billing_cycle'] = 'monthly';
            $postData['subscription_amount'] = $postData['total_amount'];
            $postData['recurring'] = '1';
        }

        // cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_SSL_VERIFYPEER => $this->sandbox ? false : true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: multipart/form-data'
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("cURL Error: $error");
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON response from SSLCommerz");
        }

        if ($httpCode !== 200) {
            throw new RuntimeException("SSLCommerz API returned HTTP $httpCode");
        }

        if ($result['status'] === 'SUCCESS' && !empty($result['GatewayPageURL'])) {
            return $result['GatewayPageURL'];
        }

        $errorMsg = $result['failedreason'] ?? $result['message'] ?? 'Unknown error';
        throw new RuntimeException("SSLCommerz Error: $errorMsg");
    }

    public function validatePayment($tran_id, $amount, $val_id = null)
    {
        $val_id = $val_id ?? $_POST['val_id'] ?? null;

        if (empty($val_id)) {
            $this->error = "Validation ID is missing";
            return false;
        }

        $validation_url = ($this->sandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com')
            . "/validator/api/validationserverAPI.php?val_id=$val_id"
            . "&store_id={$this->storeId}"
            . "&store_passwd={$this->storePassword}"
            . "&v=1&format=json";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $validation_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => $this->sandbox ? false : true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            $this->error = "cURL Error: $error";
            return false;
        }

        if ($httpCode !== 200) {
            $this->error = "Validation API returned HTTP $httpCode";
            return false;
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = "Invalid JSON response from validation API";
            return false;
        }

        $valid = $result['status'] === 'VALID'
            && $result['tran_id'] === $tran_id
            && abs((float) $result['amount'] - (float) $amount) < 0.01;

        if (!$valid) {
            $this->error = "Validation failed. Details: "
                . "Status: {$result['status']}, "
                . "TranID: {$result['tran_id']}, "
                . "Amount: {$result['amount']}";
            return false;
        }

        return true;
    }
}
