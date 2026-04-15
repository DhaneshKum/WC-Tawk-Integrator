<?php
require_once __DIR__ . '/config.php';

class WooCommerce {
    private $baseUrl;
    private $consumerKey;
    private $consumerSecret;

    public function __construct() {
        $this->baseUrl       = rtrim(WC_STORE_URL, '/') . '/wp-json/wc/v3';
        $this->consumerKey   = WC_CONSUMER_KEY;
        $this->consumerSecret = WC_CONSUMER_SECRET;
    }

    // Make API request
    private function request($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->consumerKey . ':' . $this->consumerSecret,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            return ['error' => $data['message'] ?? 'API Error: ' . $httpCode, 'code' => $httpCode];
        }

        return $data;
    }

    // POST request (for update)
    private function post($endpoint, $body = []) {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_USERPWD        => $this->consumerKey . ':' . $this->consumerSecret,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    // PUT request (for update)
    private function put($endpoint, $body = []) {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_USERPWD        => $this->consumerKey . ':' . $this->consumerSecret,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    // Get Orders
    public function getOrders($params = []) {
        $defaultParams = [
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'date',
            'order'    => 'desc',
        ];
        return $this->request('/orders', array_merge($defaultParams, $params));
    }

    // Get Single Order
    public function getOrder($orderId) {
        return $this->request('/orders/' . $orderId);
    }

    // Update Order Status
    public function updateOrderStatus($orderId, $status) {
        return $this->put('/orders/' . $orderId, ['status' => $status]);
    }

    // Get Order Count by Status
    public function getOrderStats() {
        $statuses = ['pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded'];
        $stats = [];
        foreach ($statuses as $status) {
            $result = $this->request('/orders', ['status' => $status, 'per_page' => 1]);
            // WooCommerce returns total in headers — we'll count via loop or approximate
            $stats[$status] = is_array($result) && !isset($result['error']) ? count($result) : 0;
        }
        return $stats;
    }

    // Get recent orders summary
    public function getDashboardStats() {
        $todayOrders    = $this->request('/orders', ['after' => date('Y-m-d') . 'T00:00:00', 'per_page' => 100]);
        $recentOrders   = $this->request('/orders', ['per_page' => 5]);
        $processingOrders = $this->request('/orders', ['status' => 'processing', 'per_page' => 100]);
        $pendingOrders  = $this->request('/orders', ['status' => 'pending', 'per_page' => 100]);

        $todayRevenue = 0;
        if (is_array($todayOrders) && !isset($todayOrders['error'])) {
            foreach ($todayOrders as $order) {
                $todayRevenue += floatval($order['total'] ?? 0);
            }
        }

        return [
            'today_orders'      => is_array($todayOrders) ? count($todayOrders) : 0,
            'today_revenue'     => $todayRevenue,
            'processing_orders' => is_array($processingOrders) ? count($processingOrders) : 0,
            'pending_orders'    => is_array($pendingOrders) ? count($pendingOrders) : 0,
            'recent_orders'     => is_array($recentOrders) ? $recentOrders : [],
        ];
    }

    // Get Customers
    public function getCustomers($params = []) {
        $defaultParams = ['per_page' => 20, 'page' => 1];
        return $this->request('/customers', array_merge($defaultParams, $params));
    }

    // Get Products (for reference in orders)
    public function getProducts($params = []) {
        $defaultParams = ['per_page' => 20, 'page' => 1];
        return $this->request('/products', array_merge($defaultParams, $params));
    }
}
